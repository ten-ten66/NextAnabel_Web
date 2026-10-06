/**
 * フォームの E2E テスト（サーバー版の PHP を php -S で起動して、HTTP で送信する）
 *
 *   node tests/forms.e2e.mjs [--wp=http://127.0.0.1:8300]
 *
 * 対象
 *   - 美容皮膚科の予約フォーム（sites/skin-clinic/contact.php：入力 → 確認 → 完了）
 *   - 美容外科のカウンセリング予約（sites/surgery-clinic/counseling.php：同じ FormFlow）
 *   - 医療脱毛LPの予約 API（sites/lp/api/reserve.php：JSON）
 *   - --wp を指定した場合は、起動済みの WordPress（テーマ hakuji-skin-clinic）の予約フォームも検査する
 * 観点: 正常系・入力エラー・CSRF・XSS・ヘッダーへの改行混入・ハニーポット・入力時間・二重送信・送信間隔・休診日
 * メールは送らず storage/mail.log に書かれる（MAIL_TRANSPORT 未設定時の LogMailer）。
 * 結果は qa-output/forms.json に保存する（tools/qa/metrics.mjs がサンプル集に載せる）。
 */
import { request } from 'playwright';
import { spawn } from 'node:child_process';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const args = Object.fromEntries(process.argv.slice(2).map((a) => {
  const [k, v] = a.replace(/^--/, '').split('=');
  return [k, v ?? true];
}));
const port = 8700 + Math.floor(Math.random() * 200);
const origin = `http://127.0.0.1:${port}`;
const mailLog = join(root, 'storage', 'mail.log');

const env = { ...process.env };
delete env.MAIL_TRANSPORT;
delete env.SITE_BUILD;
const server = spawn('php', ['-S', `127.0.0.1:${port}`, '-t', join(root, 'sites')], { stdio: 'ignore', env });
await new Promise((r) => setTimeout(r, 800));

let passed = 0;
let failed = 0;
const results = [];
const check = (label, ok, detail = '') => {
  if (ok) passed++;
  else failed++;
  results.push({ label, ok: Boolean(ok) });
  console.log(`${ok ? 'OK' : 'NG'}  ${label}${ok || !detail ? '' : `\n      ${detail}`}`);
};
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const mails = () => (existsSync(mailLog) ? readFileSync(mailLog, 'utf8').split('==== ').length - 1 : 0);
const lastMails = (n) => (existsSync(mailLog) ? readFileSync(mailLog, 'utf8').split('==== ').slice(-n) : []);
const tokenOf = (html) => html.match(/name="_token" value="([^"]+)"/)?.[1] ?? '';

// 東京の日付で、今日から days 日後のうち weekdayOk を満たす最初の日（YYYY-MM-DD）
const tokyoDate = (days) => new Date(Date.now() + 9 * 3600e3 + days * 86400e3);
const ymd = (d) => d.toISOString().slice(0, 10);
const findDate = (from, ok) => {
  for (let i = from; i < from + 30; i++) {
    const d = tokyoDate(i);
    if (ok(d.getUTCDay(), ymd(d))) return ymd(d);
  }
  throw new Error('条件に合う日付が見つかりません');
};
const skinClosed = (() => {
  const src = readFileSync(join(root, 'sites/skin-clinic/data/site.php'), 'utf8');
  return [...src.matchAll(/'(\d{4}-\d{2}-\d{2})'/g)].map((m) => m[1]);
})();

// FormFlow のフォームを開き、ボットとみなされない時間（3秒）待ってから送信できる状態にする
const openForm = async (path) => {
  const ctx = await request.newContext({ baseURL: origin });
  const res = await ctx.get(path);
  const html = await res.text();
  return { ctx, token: tokenOf(html), status: res.status() };
};

// ---------------------------------------------------------------------------
// 美容皮膚科の予約フォーム
// ---------------------------------------------------------------------------
{
  const path = '/skin-clinic/contact.php';
  const date = findDate(1, (w, d) => w !== 4 && !skinClosed.includes(d));
  const thursday = findDate(1, (w) => w === 4);
  const holiday = skinClosed.find((d) => d > ymd(tokyoDate(0)) && d <= ymd(tokyoDate(60)));
  const valid = {
    name: '山田 花子', kana: 'やまだ はなこ', email: 'hanako@example.com', tel: '０３−１２３４−５６７８',
    purpose: 'counseling', date1: date, time: 'am', message: '頬のシミが気になります。', consent: '1', website: '',
  };

  // 入力時間: 表示から3秒以内の送信はボットとみなし、完了画面へ送るがメールは送らない
  {
    const before = mails();
    const { ctx, token } = await openForm(path);
    const res = await ctx.post(path, { form: { ...valid, _token: token, _action: 'confirm' }, maxRedirects: 0 });
    check('皮膚科: 表示直後の送信はボット扱い（完了画面へ・メールなし）', res.status() === 303 && /step=complete/.test(res.headers().location ?? '') && mails() === before);
    await ctx.dispose();
  }

  // ハニーポット
  {
    const before = mails();
    const { ctx, token } = await openForm(path);
    await sleep(3200);
    const res = await ctx.post(path, { form: { ...valid, website: 'https://spam.example', _token: token, _action: 'confirm' }, maxRedirects: 0 });
    check('皮膚科: ハニーポットに入力があれば送信しない', res.status() === 303 && mails() === before);
    await ctx.dispose();
  }

  // CSRF
  {
    const { ctx } = await openForm(path);
    await sleep(3200);
    const res = await ctx.post(path, { form: { ...valid, _token: 'invalid-token', _action: 'confirm' }, maxRedirects: 0 });
    check('皮膚科: CSRF トークンが違えば 400', res.status() === 400 && (await res.text()).includes('一定時間が経過したため'));
    await ctx.dispose();
  }

  // 入力エラー・休診日・改行混入
  {
    const { ctx, token } = await openForm(path);
    await sleep(3200);
    let res = await ctx.post(path, { form: { ...valid, name: '', kana: 'yamada', date1: thursday, consent: '', _token: token, _action: 'confirm' }, maxRedirects: 0 });
    let html = await res.text();
    check('皮膚科: 必須・カナ・定休日（木曜）・同意のエラーで 422', res.status() === 422
      && html.includes('お名前を入力してください') && html.includes('カタカナ') && html.includes('休診日') && html.includes('プライバシーポリシーへの同意が必要です'),
    `status ${res.status()}`);
    if (holiday) {
      res = await ctx.post(path, { form: { ...valid, date1: holiday, _token: tokenOf(html), _action: 'confirm' }, maxRedirects: 0 });
      html = await res.text();
      check(`皮膚科: 祝日（${holiday}）は受け付けない`, res.status() === 422 && html.includes('休診日'));
    }
    res = await ctx.post(path, { form: { ...valid, email: 'hanako@example.com\r\nBcc: victim@example.com', _token: tokenOf(html), _action: 'confirm' }, maxRedirects: 0 });
    html = await res.text();
    check('皮膚科: メールアドレスへの改行混入（ヘッダーインジェクション）は 422', res.status() === 422 && html.includes('メールアドレス'));
    await ctx.dispose();
  }

  // 正常系（XSS の無害化・送信・完了・二重送信・送信間隔）
  {
    const before = mails();
    const { ctx, token } = await openForm(path);
    await sleep(3200);
    const xss = '<script>alert("xss")</script>頬のシミ';
    let res = await ctx.post(path, { form: { ...valid, message: xss, _token: token, _action: 'confirm' }, maxRedirects: 0 });
    let html = await res.text();
    check('皮膚科: 確認画面へ進む（ひらがな・全角数字を正規化）', res.status() === 200 && html.includes('入力内容の確認') && html.includes('ヤマダ ハナコ') && html.includes('03-1234-5678'));
    check('皮膚科: 確認画面でスクリプトはエスケープされる', html.includes('&lt;script&gt;') && !html.includes('<script>alert('));
    const confirmToken = tokenOf(html);
    res = await ctx.post(path, { form: { _token: confirmToken, _action: 'send' }, maxRedirects: 0 });
    check('皮膚科: 送信は 303 で完了画面へ（PRG）', res.status() === 303 && /step=complete/.test(res.headers().location ?? ''));
    check('皮膚科: 院内宛て・自動返信の2通を記録', mails() === before + 2, `メール ${mails() - before} 通`);
    const [admin, reply] = lastMails(2);
    check('皮膚科: 自動返信にご相談内容（自由記述）を含めない', !!reply && !reply.includes('頬のシミ') && admin.includes('頬のシミ'));
    res = await ctx.get(`${path}?step=complete`, { maxRedirects: 0 });
    check('皮膚科: 完了画面は送信直後に1回だけ表示', res.status() === 200 && (await res.text()).includes('送信が完了しました'));
    res = await ctx.get(`${path}?step=complete`, { maxRedirects: 0 });
    check('皮膚科: 完了画面の再読み込みは入力画面へ戻す', res.status() === 303);
    res = await ctx.post(path, { form: { _token: confirmToken, _action: 'send' }, maxRedirects: 0 });
    check('皮膚科: 同じ内容の二重送信はメールを送らない', res.status() === 400 && mails() === before + 2, `status ${res.status()}`);
    // 送信間隔の制限（30秒）
    res = await ctx.get(path);
    html = await res.text();
    await sleep(3200);
    res = await ctx.post(path, { form: { ...valid, _token: tokenOf(html), _action: 'confirm' }, maxRedirects: 0 });
    html = await res.text();
    res = await ctx.post(path, { form: { _token: tokenOf(html), _action: 'send' }, maxRedirects: 0 });
    check('皮膚科: 30秒以内の再送信は 429', res.status() === 429 && mails() === before + 2, `status ${res.status()}`);
    await ctx.dispose();
  }
}

// ---------------------------------------------------------------------------
// 美容外科のカウンセリング予約
// ---------------------------------------------------------------------------
{
  const path = '/surgery-clinic/counseling.php';
  const page = await (await request.newContext({ baseURL: origin })).get(path);
  const menu = (await page.text()).match(/name="menu\[\]" value="([^"]+)"/)?.[1] ?? '';
  const sunday = findDate(1, (w) => w === 0);
  const surgeryClosed = [...readFileSync(join(root, 'sites/surgery-clinic/data/site.php'), 'utf8').matchAll(/'(\d{4}-\d{2}-\d{2})'/g)].map((m) => m[1]);
  const holiday = surgeryClosed.find((d) => d > ymd(tokyoDate(0)) && d <= ymd(tokyoDate(60)) && new Date(`${d}T00:00:00Z`).getUTCDay() !== 0);
  const date = findDate(1, (w, d) => w !== 0 && !surgeryClosed.includes(d));
  const valid = { name: '佐藤 一郎', kana: 'サトウ イチロウ', email: 'ichiro@example.com', tel: '090-1234-5678', 'menu[]': menu, date1: date, time: 'morning', consent: '1', website: '' };

  const before = mails();
  const { ctx, token } = await openForm(path);
  await sleep(3200);
  let res = await ctx.post(path, { form: { ...valid, date1: sunday, _token: token, _action: 'confirm' }, maxRedirects: 0 });
  let html = await res.text();
  check('外科: 日曜日は受け付けない', res.status() === 422 && html.includes('休診日'));
  if (holiday) {
    res = await ctx.post(path, { form: { ...valid, date1: holiday, _token: tokenOf(html), _action: 'confirm' }, maxRedirects: 0 });
    html = await res.text();
    check(`外科: 祝日（${holiday}）は受け付けない`, res.status() === 422 && html.includes('休診日'));
  }
  res = await ctx.post(path, { form: { ...valid, _token: tokenOf(html), _action: 'confirm' }, maxRedirects: 0 });
  html = await res.text();
  check('外科: 確認画面へ進む', res.status() === 200 && Boolean(menu), `status ${res.status()} menu=${menu}`);
  res = await ctx.post(path, { form: { _token: tokenOf(html), _action: 'send' }, maxRedirects: 0 });
  check('外科: 送信して完了画面へ・2通を記録', res.status() === 303 && mails() === before + 2, `status ${res.status()} メール ${mails() - before} 通`);
  await ctx.dispose();
}

// ---------------------------------------------------------------------------
// 医療脱毛LPの予約 API（JSON）
// ---------------------------------------------------------------------------
{
  const api = '/lp/api/reserve.php';
  const ctx = await request.newContext({ baseURL: origin });
  const page = await ctx.get('/lp/index.php');
  const html = await page.text();
  const token = html.match(/<meta name="csrf-token" content="([^"]+)"/)?.[1] ?? '';
  const part = html.match(/name="parts\[\]" value="([^"]+)"/)?.[1] ?? '';
  const lpClosed = skinClosed; // LP は皮膚科と同じ医院（同じ site.php を読み込む）
  const date = findDate(1, (w, d) => w !== 4 && !lpClosed.includes(d));
  const body = { _token: token, name: '鈴木 さくら', tel: '080-1234-5678', email: 'sakura@example.com', parts: [part], date1: date, time1: 'afternoon', message: '', consent: '1', website: '' };
  const json = { 'Content-Type': 'application/json' };

  let res = await ctx.get(api);
  check('LP: GET は 405', res.status() === 405);
  res = await ctx.post(api, { data: body, headers: json });
  check('LP: Origin のない送信は 403', res.status() === 403);
  res = await ctx.post(api, { data: body, headers: { ...json, Origin: 'https://evil.example' } });
  check('LP: 別オリジンからの送信は 403', res.status() === 403);
  res = await ctx.post(api, { data: { ...body, _token: 'invalid' }, headers: { ...json, Origin: origin } });
  check('LP: CSRF トークンが違えば 403', res.status() === 403);
  let before = mails();
  res = await ctx.post(api, { data: body, headers: { ...json, Origin: origin } });
  check('LP: 表示直後の送信はボット扱い（ok を返すがメールなし）', res.status() === 200 && (await res.json()).ok === true && mails() === before);
  await sleep(3200);
  res = await ctx.post(api, { data: { ...body, date1: findDate(1, (w) => w === 4), email: 'bad' }, headers: { ...json, Origin: origin } });
  const errors = (await res.json()).errors ?? {};
  check('LP: 入力エラー（定休日・メール形式）は 422 と項目ごとのメッセージ', res.status() === 422 && 'date1' in errors && 'email' in errors, JSON.stringify(errors));
  before = mails();
  res = await ctx.post(api, { data: body, headers: { ...json, Origin: origin } });
  check('LP: 正常に送信できる（2通を記録）', res.status() === 200 && mails() === before + 2 && Boolean(part), `status ${res.status()} part=${part}`);
  res = await ctx.post(api, { data: body, headers: { ...json, Origin: origin } });
  check('LP: 30秒以内の再送信は 429', res.status() === 429 && mails() === before + 2);
  await ctx.dispose();
}

// ---------------------------------------------------------------------------
// WordPress テーマ版（起動済みのサーバーを指定した場合のみ）
// ---------------------------------------------------------------------------
if (args.wp) {
  const base = String(args.wp).replace(/\/$/, '');
  const ctx = await request.newContext({ baseURL: base });
  const page = await (await ctx.get('/contact/')).text();
  const nonce = page.match(/name="hakuji_nonce" value="([^"]+)"/)?.[1] ?? '';
  const post = (form) => ctx.post('/wp-admin/admin-post.php', { form: { action: 'hakuji_contact', ...form }, headers: { Referer: `${base}/contact/` }, maxRedirects: 0 });
  const thursday = findDate(2, (w) => w === 4);
  let res = await post({ hakuji_nonce: 'invalid', name: 'x' });
  check('WP: nonce が違えば 400', res.status() === 400);
  res = await post({ hakuji_nonce: nonce, website: 'https://spam.example' });
  check('WP: ハニーポットは完了表示へ（送信しない）', res.status() === 303 && /sent=1/.test(res.headers().location ?? ''));
  res = await post({ hakuji_nonce: nonce, name: '<b>山田</b>', kana: 'yamada', email: 'a@example.com\r\nBcc: b@example.com', tel: '03-1234-5678', date1: thursday, time: 'am' });
  const location = res.headers().location ?? '';
  const errorsPage = location ? await (await ctx.get(location)).text() : '';
  check('WP: 入力エラーは 303 で入力画面へ戻し、エラー一覧を表示', res.status() === 303 && /form=/.test(location)
    && errorsPage.includes('カタカナ') && errorsPage.includes('メールアドレスの形式') && errorsPage.includes('休診日') && errorsPage.includes('チェックを入れてください'));
  check('WP: 入力値のタグは除去・エスケープされる', !errorsPage.includes('<b>山田</b>'));
  await ctx.dispose();
}

server.kill();
mkdirSync(join(root, 'qa-output'), { recursive: true });
writeFileSync(join(root, 'qa-output', 'forms.json'), JSON.stringify({ testedAt: new Date().toISOString(), wordpress: Boolean(args.wp), passed, failed, results }, null, 2));
console.log(`\n${passed + failed} 件中 ${passed} 件合格`);
process.exit(failed ? 1 : 0);
