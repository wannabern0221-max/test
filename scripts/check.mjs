import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const readJson = (file) => JSON.parse(fs.readFileSync(path.join(root, file), 'utf8'));
const fail = (message) => { console.error(`✖ ${message}`); process.exitCode = 1; };
const regions = readJson('data/regions.json');

const slugs = new Set();
for (const region of regions) {
  if (!region.name || !region.slug || !region.status) fail(`지역 필수값 누락: ${JSON.stringify(region)}`);
  if (slugs.has(region.slug)) fail(`중복 지역 slug: ${region.slug}`);
  slugs.add(region.slug);
  if (region.status === 'verified') {
    const feePath = path.join(root, 'data', 'fees', `${region.slug}.json`);
    if (!fs.existsSync(feePath)) fail(`${region.name}: 검증 완료인데 수수료 파일이 없습니다.`);
    if (!region.sourceUrl || !region.verifiedAt) fail(`${region.name}: sourceUrl/verifiedAt이 없습니다.`);
  }
}

for (const file of fs.readdirSync(path.join(root, 'data', 'fees')).filter((name) => name.endsWith('.json'))) {
  const rows = readJson(`data/fees/${file}`);
  rows.forEach((row, i) => {
    for (const key of ['category','item','itemSlug','spec','fee']) if (row[key] === undefined || row[key] === '') fail(`${file} ${i + 1}행: ${key} 누락`);
    if (!Number.isInteger(row.fee) || row.fee < 0) fail(`${file} ${i + 1}행: fee는 0 이상의 정수여야 합니다.`);
  });
}

if (!process.exitCode) console.log(`✓ 데이터 검사 완료: ${regions.length}개 지역`);
