import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const out = path.join(root, 'dist');
const readJson = (file) => JSON.parse(fs.readFileSync(path.join(root, file), 'utf8'));
const regions = readJson('data/regions.json');
const siteUrl = (process.env.SITE_URL || 'http://localhost:4173').replace(/\/$/, '');
const basePath = (process.env.BASE_PATH || '').replace(/\/$/, '');
const isLocal = /^https?:\/\/(localhost|127\.0\.0\.1)/.test(siteUrl);
const today = new Date().toISOString().slice(0, 10);

fs.rmSync(out, { recursive: true, force: true });
fs.mkdirSync(out, { recursive: true });

const esc = (value = '') => String(value).replace(/[&<>'"]/g, (char) => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#39;', '"':'&quot;' }[char]));
const money = (value) => new Intl.NumberFormat('ko-KR').format(value) + '원';
const href = (route = '/') => `${basePath}${route.startsWith('/') ? route : '/' + route}` || '/';
const absolute = (route = '/') => `${siteUrl}${route.startsWith('/') ? route : '/' + route}`;
const write = (route, content) => {
  const clean = route === '/' ? '' : route.replace(/^\//, '').replace(/\/$/, '');
  const dir = path.join(out, clean);
  fs.mkdirSync(dir, { recursive: true });
  fs.writeFileSync(path.join(dir, 'index.html'), content, 'utf8');
};

const logo = () => `<a class="brand" href="${href('/')}" aria-label="버려줘 홈">
  <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M7.5 7.5h9M9 7.5l.6 10h4.8l.6-10M9.7 4.8h4.6M8.2 7.5l.6-1.8h6.4l.6 1.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
  <span>버려줘</span>
</a>`;

const header = () => `<a class="skip-link" href="#main">본문 바로가기</a>
<header class="site-header"><div class="container nav">${logo()}<nav class="nav-links" aria-label="주요 메뉴">
  <a href="${href('/#how')}">버리는 법</a>
  <a href="${href('/seoul/')}">지역별 보기</a>
  <a href="${href('/data-policy/')}">데이터 원칙</a>
  <a class="nav-cta" href="${href('/#search')}">가격 찾기</a>
</nav></div></header>`;

const footer = () => `<footer class="site-footer"><div class="container">
  <div class="footer-grid"><div>${logo()}<p class="footer-note">버려줘는 지자체 공식 공개자료를 보기 쉽게 정리하는 생활정보 서비스입니다. 실제 배출 전에는 연결된 공식 페이지에서 최종 금액과 신청 내용을 한 번 더 확인해주세요.</p></div>
  <div class="footer-links"><a href="${href('/data-policy/')}">데이터 원칙</a><a href="${href('/privacy/')}">개인정보처리방침</a><a href="${href('/seoul/')}">서울 지역</a></div></div>
  <div class="copyright">© 2026 버려줘. 정부기관 또는 지자체 공식 서비스가 아닙니다.</div>
</div></footer><div class="toast" role="status" aria-live="polite"></div>`;

function layout({ title, description, route = '/', body, robots = 'index,follow', jsonLd = [] }) {
  const canonical = absolute(route);
  const schema = jsonLd.length ? `<script type="application/ld+json">${JSON.stringify(jsonLd.length === 1 ? jsonLd[0] : jsonLd).replace(/</g, '\\u003c')}</script>` : '';
  return `<!doctype html><html lang="ko" data-base-path="${esc(basePath)}"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>${esc(title)}</title><meta name="description" content="${esc(description)}"><meta name="robots" content="${isLocal ? 'noindex,nofollow' : robots}">
<link rel="canonical" href="${esc(canonical)}"><meta name="theme-color" content="#22664f"><meta name="color-scheme" content="light">
<meta property="og:type" content="website"><meta property="og:locale" content="ko_KR"><meta property="og:site_name" content="버려줘"><meta property="og:title" content="${esc(title)}"><meta property="og:description" content="${esc(description)}"><meta property="og:url" content="${esc(canonical)}">
<meta name="twitter:card" content="summary"><meta name="twitter:title" content="${esc(title)}"><meta name="twitter:description" content="${esc(description)}">
<link rel="icon" href="${href('/assets/favicon.svg')}" type="image/svg+xml"><link rel="stylesheet" href="${href('/assets/styles.css')}">${schema}</head><body>${header()}${body}${footer()}<script src="${href('/assets/app.js')}" defer></script></body></html>`;
}

const verifiedRegions = regions.filter((r) => r.status === 'verified');
const regionFees = new Map();
for (const region of verifiedRegions) regionFees.set(region.slug, readJson(`data/fees/${region.slug}.json`).map((row) => ({ ...row, sourceUrl: region.sourceUrl, verifiedAt: region.verifiedAt })));

const uniqueItems = (rows) => {
  const map = new Map();
  for (const row of rows) if (!map.has(row.itemSlug)) map.set(row.itemSlug, row);
  return [...map.values()];
};

const allVerifiedFees = [...regionFees.entries()].flatMap(([regionSlug, rows]) => {
  const region = regions.find((r) => r.slug === regionSlug);
  return rows.map((row) => ({ ...row, regionSlug, regionName: region.name }));
});

const datalistOptions = uniqueItems(allVerifiedFees).map((row) => `<option value="${esc(row.item)}"></option>`).join('');
const regionOptions = regions.map((r) => `<option value="${esc(r.slug)}">${esc(r.name)}${r.status === 'verified' ? ' · 가격 확인 가능' : ' · 준비중'}</option>`).join('');

function searchForm({ compact = false } = {}) {
  return `<form class="search-form" data-search-form id="search">
    <div class="field"><label for="region">지역</label><div class="field-control"><select id="region" name="region" required><option value="">구를 선택해주세요</option>${regionOptions}</select></div></div>
    <div class="field"><label for="item">버릴 물건</label><div class="field-control"><input id="item" name="item" list="item-list" autocomplete="off" placeholder="예: 침대, 책상, 의자"></div><datalist id="item-list">${datalistOptions}</datalist></div>
    <button class="search-btn" type="submit">${compact ? '검색' : '수수료 바로 찾기'}</button>
  </form>`;
}

const faqData = [
  ['수수료는 왜 지역마다 다른가요?', '대형폐기물 배출 방식과 수수료 기준은 자치구 조례와 운영 기준에 따라 달라질 수 있습니다. 버려줘는 지역을 먼저 선택하도록 설계해 다른 지역의 가격을 잘못 보는 일을 줄였습니다.'],
  ['검색 결과의 가격만 보고 결제해도 되나요?', '아니요. 버려줘는 빠른 확인을 돕는 정보 서비스입니다. 실제 신청 직전에는 반드시 연결된 지자체 공식 페이지에서 품목·규격·최종 수수료를 확인해주세요.'],
  ['냉장고나 세탁기도 대형폐기물로 신고해야 하나요?', '일부 대형 폐가전은 별도의 무상방문수거 대상이 될 수 있습니다. 제품 상태와 대상 여부에 따라 달라질 수 있으므로 일반 대형폐기물로 결제하기 전에 공식 안내를 먼저 확인하는 편이 좋습니다.'],
  ['우리 구가 아직 준비중으로 나오는데요?', '공식 출처와 확인일을 함께 검증한 지역만 가격을 공개하고 있습니다. 준비중 지역에는 임의의 가격을 넣지 않습니다.']
];

const homeSchema = [
  { '@context':'https://schema.org', '@type':'WebSite', name:'버려줘', url:absolute('/'), description:'지역별 대형폐기물 수수료와 신고 정보를 빠르게 찾는 생활정보 서비스', potentialAction:{ '@type':'SearchAction', target:`${absolute('/search/')}?q={search_term_string}`, 'query-input':'required name=search_term_string' } },
  { '@context':'https://schema.org', '@type':'FAQPage', mainEntity:faqData.map(([q,a]) => ({ '@type':'Question', name:q, acceptedAnswer:{ '@type':'Answer', text:a } })) }
];

const popular = ['bed','desk','chair','refrigerator','bedding','carpet','shoe-cabinet','tv-stand'].map((slug) => allVerifiedFees.find((r) => r.itemSlug === slug)).filter(Boolean);
const regionCards = regions.map((r) => `<a class="region-card" href="${href(`/seoul/${r.slug}/`)}"><strong>${esc(r.name)}</strong><span class="status ${r.status}">${r.status === 'verified' ? '● 가격 확인 가능' : '준비중'}</span></a>`).join('');

const homeBody = `<div class="notice-bar"><div class="container notice-inner"><span class="notice-dot"></span><span>현재 강남구 공식 수수료 데이터 확인 완료 · 서울 전 지역 순차 확대 중</span></div></div>
<main id="main">
<section class="hero"><div class="container hero-grid"><div>
  <div class="eyebrow">대형폐기물, 찾는 데 오래 걸리지 않게</div>
  <h1>버리기 전에,<br><span>10초만.</span></h1>
  <p class="hero-copy">지역과 물건만 고르면 수수료와 규격을 한눈에 보여드려요. 복잡한 구청 페이지를 헤매지 않고, 마지막에는 공식 신고 페이지로 바로 이어집니다.</p>
  <div class="hero-meta"><span><i class="check">✓</i>공식 출처 표시</span><span><i class="check">✓</i>확인일 관리</span><span><i class="check">✓</i>모바일 최적화</span></div>
</div><div class="search-card">
  <div class="search-top"><div><div class="search-title">어디서, 뭘 버리세요?</div><div class="search-kicker">지역을 먼저 선택하면 더 정확해요.</div></div><span class="search-badge">서울 베타</span></div>
  ${searchForm()}<p class="mini-note">가격은 지자체 공개자료를 기준으로 정리합니다. 실제 배출 전 공식 신청 페이지에서 최종 내용을 확인해주세요.</p>
</div></div></section>
<section class="section-tight"><div class="container"><div class="trust-grid">
  <div class="trust-card"><div class="trust-icon">01</div><strong>출처 없는 가격은 싣지 않아요</strong><p>공식 페이지와 확인일이 연결된 데이터만 가격 검색에 노출합니다.</p></div>
  <div class="trust-card"><div class="trust-icon">02</div><strong>규격까지 같이 보여드려요</strong><p>같은 침대라도 1인용·2인용·매트리스·프레임의 금액이 다를 수 있어요.</p></div>
  <div class="trust-card"><div class="trust-icon">03</div><strong>신고는 공식 사이트에서 마무리</strong><p>버려줘는 정보 확인을 돕고, 결제와 신고는 지자체 공식 서비스로 연결합니다.</p></div>
</div></div></section>
<section class="section"><div class="container"><div class="section-head"><div><h2 class="section-title">많이 찾는 물건부터</h2><p class="section-desc">일상에서 자주 버리는 품목을 바로 확인해보세요.</p></div></div><div class="chips">${popular.map((r) => `<a class="chip" href="${href(`/seoul/${r.regionSlug}/${r.itemSlug}/`)}">${esc(r.item)}</a>`).join('')}</div></div></section>
<section class="section" id="how"><div class="container"><div class="section-head"><div><h2 class="section-title">버리는 순서도<br>딱 세 단계면 돼요.</h2></div></div><div class="steps">
  <article class="step"><div class="step-label">지역 선택</div><h3>내가 사는 구를 고르기</h3><p>자치구마다 수수료와 신고 방식이 다를 수 있어 지역을 가장 먼저 확인합니다.</p></article>
  <article class="step"><div class="step-label">가격 확인</div><h3>물건과 규격 확인하기</h3><p>품목 이름뿐 아니라 크기와 형태를 같이 보고 실제 적용되는 수수료를 확인합니다.</p></article>
  <article class="step"><div class="step-label">공식 신고</div><h3>구청 서비스에서 신청하기</h3><p>가격 확인이 끝나면 연결된 공식 페이지에서 최종 확인 후 신고를 마칩니다.</p></article>
</div></div></section>
<section class="section"><div class="container"><div class="section-head"><div><h2 class="section-title">서울 25개 구</h2><p class="section-desc">검증이 끝난 지역부터 가격 검색을 열고 있습니다.</p></div><a class="text-link" href="${href('/seoul/')}">전체 보기 →</a></div><div class="region-grid">${regionCards}</div></div></section>
<section class="section"><div class="container"><div class="info-panel"><div><h3>“준비중”도 일부러 보여드려요.</h3><p>인터넷에는 오래된 가격표나 다른 지역의 수수료가 섞여 있는 경우가 있습니다. 버려줘는 빈칸을 억지로 채우기보다, 공식 자료를 확인하지 못한 지역은 준비중으로 남겨둡니다.</p></div><div class="info-aside"><strong>데이터 공개 기준</strong><br>① 지자체 공식 출처 확인<br>② 품목·규격·금액 구조화<br>③ 확인일 기록<br>④ 검색 페이지 생성<br>⑤ 변경사항 주기적 점검</div></div></div></section>
<section class="section"><div class="container"><div class="section-head"><div><h2 class="section-title">자주 묻는 질문</h2></div></div><div class="faq-list">${faqData.map(([q,a]) => `<details><summary>${esc(q)}</summary><p>${esc(a)}</p></details>`).join('')}</div></div></section>
</main>`;

write('/', layout({ title:'버려줘 | 지역별 대형폐기물 가격·신고 검색', description:'지역과 품목만 고르면 대형폐기물 수수료, 규격, 공식 신고 페이지를 빠르게 확인할 수 있어요.', route:'/', body:homeBody, jsonLd:homeSchema }));

const seoulBody = `<main id="main"><section class="page-hero"><div class="container"><div class="breadcrumbs"><a href="${href('/')}">홈</a><span>/</span><b>서울</b></div><h1>서울 대형폐기물<br>지역별 가격 찾기</h1><p class="page-subtitle">서울 25개 자치구를 한곳에 모았습니다. 가격은 공식 출처 검증이 끝난 지역부터 공개합니다.</p></div></section><section class="section-tight"><div class="container"><div class="region-grid">${regionCards}</div></div></section></main>`;
write('/seoul/', layout({ title:'서울 대형폐기물 가격·신고 | 버려줘', description:'서울 25개 구 대형폐기물 수수료와 신고 정보를 지역별로 찾아보세요.', route:'/seoul/', body:seoulBody }));

for (const region of regions) {
  const route = `/seoul/${region.slug}/`;
  const crumbs = `<div class="breadcrumbs"><a href="${href('/')}">홈</a><span>/</span><a href="${href('/seoul/')}">서울</a><span>/</span><b>${esc(region.name)}</b></div>`;
  if (region.status !== 'verified') {
    const body = `<main id="main"><section class="page-hero"><div class="container">${crumbs}<h1>${esc(region.name)} 대형폐기물</h1><p class="page-subtitle">${esc(region.name)}의 공식 수수료 자료를 검증하고 있습니다.</p></div></section><section class="section-tight"><div class="container"><div class="pending-box"><div class="icon">⌛</div><h2>아직 가격을 공개하지 않았어요.</h2><p>정확한 공식 출처와 최신 수수료를 확인한 뒤 공개합니다. 다른 지역의 금액을 임의로 적용하지 않습니다.</p></div></div></section></main>`;
    write(route, layout({ title:`${region.name} 대형폐기물 가격 | 준비중 · 버려줘`, description:`${region.name} 대형폐기물 수수료 정보를 공식 자료 기준으로 확인 중입니다.`, route, body, robots:'noindex,follow' }));
    continue;
  }
  const rows = regionFees.get(region.slug);
  const categories = [...new Set(rows.map((r) => r.category))];
  const categoryHtml = categories.map((category) => {
    const items = rows.filter((r) => r.category === category);
    return `<section class="category-block" data-category-block><h2 class="category-title"><span>${esc(category)}</span><span class="category-count">${items.length}개 규격</span></h2><div class="fee-grid">${items.map((r) => `<a class="fee-card" data-fee-card data-search-text="${esc([r.item,r.spec,...(r.aliases||[])].join(' '))}" href="${href(`/seoul/${region.slug}/${r.itemSlug}/`)}"><div class="fee-card-main"><strong>${esc(r.item)}</strong><small>${esc(r.spec)}</small></div><div class="fee">${money(r.fee)}</div></a>`).join('')}</div></section>`;
  }).join('');
  const regionSchema = { '@context':'https://schema.org', '@type':'CollectionPage', name:`${region.name} 대형폐기물 수수료`, description:`${region.name} 대형폐기물 품목별 규격과 수수료`, url:absolute(route), isBasedOn:region.sourceUrl, dateModified:region.verifiedAt };
  const body = `<main id="main"><section class="page-hero"><div class="container">${crumbs}<h1>${esc(region.name)} 대형폐기물<br>수수료 한눈에 보기</h1><p class="page-subtitle">품목 이름을 검색하거나 아래 목록에서 규격을 확인하세요. 표시된 정보는 공식 공개자료를 기준으로 정리했습니다.</p><div class="source-row"><span class="status verified">● 확인 완료</span><a class="source-pill" href="${esc(region.sourceUrl)}" target="_blank" rel="noopener">출처 · ${esc(region.sourceName)} ↗</a><span class="source-pill">확인일 · ${esc(region.verifiedAt)}</span></div></div></section><section class="section-tight"><div class="container"><div class="toolbar"><input type="search" data-fee-filter placeholder="품목 검색 · 침대, 책상, 의자..." aria-label="${esc(region.name)} 품목 검색"></div>${categoryHtml}<div class="cta-box"><div><h3>가격을 확인했나요?</h3><p>실제 신고 전에는 공식 페이지에서 품목과 최종 수수료를 한 번 더 확인해주세요.</p></div><a class="cta-button" href="${esc(region.applyUrl)}" target="_blank" rel="noopener">${esc(region.name)} 공식 신고 페이지 ↗</a></div></div></section></main>`;
  write(route, layout({ title:`${region.name} 대형폐기물 가격·수수료 | 버려줘`, description:`${region.name} 대형폐기물 품목별 규격과 수수료를 확인하고 공식 신고 페이지로 이동하세요.`, route, body, jsonLd:[regionSchema] }));

  const itemGroups = new Map();
  for (const row of rows) {
    if (!itemGroups.has(row.itemSlug)) itemGroups.set(row.itemSlug, []);
    itemGroups.get(row.itemSlug).push(row);
  }
  for (const [itemSlug, itemRows] of itemGroups) {
    const item = itemRows[0].item;
    const itemRoute = `/seoul/${region.slug}/${itemSlug}/`;
    const fees = itemRows.map((r) => r.fee);
    const low = Math.min(...fees), high = Math.max(...fees);
    const feeText = low === high ? money(low) : `${money(low)} ~ ${money(high)}`;
    const itemCrumbs = `<div class="breadcrumbs"><a href="${href('/')}">홈</a><span>/</span><a href="${href('/seoul/')}">서울</a><span>/</span><a href="${href(route)}">${esc(region.name)}</a><span>/</span><b>${esc(item)}</b></div>`;
    const table = `<table class="price-table"><thead><tr><th>품목</th><th>규격</th><th>수수료</th></tr></thead><tbody>${itemRows.map((r) => `<tr><td>${esc(r.item)}</td><td>${esc(r.spec)}</td><td>${money(r.fee)}</td></tr>`).join('')}</tbody></table>`;
    const related = uniqueItems(rows).filter((r) => r.itemSlug !== itemSlug).slice(0, 8);
    const itemSchema = [
      { '@context':'https://schema.org', '@type':'WebPage', name:`${region.name} ${item} 대형폐기물 가격`, description:`${region.name}에서 ${item}을 버릴 때 필요한 규격별 수수료 정보`, url:absolute(itemRoute), isBasedOn:region.sourceUrl, dateModified:region.verifiedAt },
      { '@context':'https://schema.org', '@type':'BreadcrumbList', itemListElement:[
        { '@type':'ListItem', position:1, name:'홈', item:absolute('/') },
        { '@type':'ListItem', position:2, name:'서울', item:absolute('/seoul/') },
        { '@type':'ListItem', position:3, name:region.name, item:absolute(route) },
        { '@type':'ListItem', position:4, name:item, item:absolute(itemRoute) }
      ]}
    ];
    const body = `<main id="main"><section class="page-hero"><div class="container">${itemCrumbs}<div class="eyebrow">${esc(region.name)} · ${esc(item)}</div><h1>${esc(item)} 버릴 때<br><span style="color:var(--brand)">${esc(feeText)}</span></h1><p class="page-subtitle">규격에 따라 금액이 달라질 수 있어요. 아래 표에서 내 물건과 가장 가까운 규격을 확인하세요.</p><div class="source-row"><span class="status verified">● 공식 출처 확인</span><span class="source-pill">확인일 · ${esc(region.verifiedAt)}</span></div></div></section><section class="section-tight"><div class="container">${table}<div class="cta-box"><div><h3>${esc(region.name)}에서 ${esc(item)} 신고하기</h3><p>신청 페이지에서 실제 품목과 규격을 다시 선택한 뒤 최종 금액을 확인해주세요.</p></div><a class="cta-button" href="${esc(region.applyUrl)}" target="_blank" rel="noopener">공식 신고 페이지 ↗</a></div><div class="info-panel"><div><h3>이 가격은 어디서 왔나요?</h3><p>${esc(region.sourceName)}에 공개된 품목별 부과 기준을 구조화했습니다. 출처 페이지가 변경되거나 수수료가 개정될 수 있으므로 실제 배출 직전 공식 페이지 확인이 필요합니다.</p></div><div class="info-aside"><strong>데이터 정보</strong><br>지역: ${esc(region.city)} ${esc(region.name)}<br>품목: ${esc(item)}<br>확인일: ${esc(region.verifiedAt)}<br><a class="text-link" href="${esc(region.sourceUrl)}" target="_blank" rel="noopener">원문 보기 ↗</a></div></div><section class="section"><div class="section-head"><div><h2 class="section-title">다른 품목도 찾나요?</h2></div></div><div class="chips">${related.map((r) => `<a class="chip" href="${href(`/seoul/${region.slug}/${r.itemSlug}/`)}">${esc(r.item)}</a>`).join('')}</div></section></div></section></main>`;
    write(itemRoute, layout({ title:`${region.name} ${item} 대형폐기물 가격·수수료 | 버려줘`, description:`${region.name}에서 ${item} 버릴 때 규격별 대형폐기물 수수료 ${feeText}. 공식 출처와 확인일을 함께 확인하세요.`, route:itemRoute, body, jsonLd:itemSchema }));
  }
}

const searchBody = `<main id="main"><section class="page-hero"><div class="container"><div class="breadcrumbs"><a href="${href('/')}">홈</a><span>/</span><b>검색</b></div><h1 data-query-label>검색 결과</h1><p class="page-subtitle">현재 공식 데이터 검증이 끝난 지역 안에서 검색합니다.</p></div></section><section class="section-tight"><div class="container"><div class="search-card" style="margin-bottom:22px">${searchForm({compact:true})}</div><div class="search-results" data-search-results></div></div></section></main>`;
write('/search/', layout({ title:'대형폐기물 검색 | 버려줘', description:'지역과 품목으로 대형폐기물 수수료를 검색합니다.', route:'/search/', body:searchBody, robots:'noindex,follow' }));

const dataPolicyBody = `<main id="main"><section class="page-hero"><div class="container"><div class="breadcrumbs"><a href="${href('/')}">홈</a><span>/</span><b>데이터 원칙</b></div><h1>가격보다 먼저<br>확인하는 것.</h1><p class="page-subtitle">버려줘는 ‘많은 페이지’보다 ‘틀리지 않는 페이지’를 먼저 만듭니다.</p></div></section><section class="section-tight"><div class="container"><div class="trust-grid"><div class="trust-card"><div class="trust-icon">A</div><strong>공식 출처 우선</strong><p>지자체 공식 홈페이지, 조례 또는 공식 공개자료를 우선합니다.</p></div><div class="trust-card"><div class="trust-icon">B</div><strong>확인일 기록</strong><p>가격 데이터에는 마지막으로 공식 자료를 확인한 날짜를 남깁니다.</p></div><div class="trust-card"><div class="trust-icon">C</div><strong>미확인은 미공개</strong><p>추측하거나 다른 지역의 금액을 복사해 빈 지역을 채우지 않습니다.</p></div></div><div class="info-panel" style="margin-top:20px"><div><h3>오류를 발견하셨나요?</h3><p>지자체 정책과 수수료는 바뀔 수 있습니다. 현재 공개 버전은 데이터 수정 창구를 별도로 연결하지 않았으며, 운영 단계에서 GitHub Issues 또는 전용 문의 채널을 추가하는 것을 권장합니다.</p></div><div class="info-aside"><strong>현재 공개 범위</strong><br>서울특별시 25개 구 구조 생성<br>강남구 수수료 데이터 공개<br>나머지 지역은 검증 전까지 noindex 처리</div></div></div></section></main>`;
write('/data-policy/', layout({ title:'데이터 원칙 | 버려줘', description:'버려줘가 대형폐기물 가격 데이터를 확인하고 공개하는 기준입니다.', route:'/data-policy/', body:dataPolicyBody }));

const privacyBody = `<main id="main"><section class="page-hero"><div class="container"><div class="breadcrumbs"><a href="${href('/')}">홈</a><span>/</span><b>개인정보처리방침</b></div><h1>개인정보처리방침</h1><p class="page-subtitle">현재 버려줘 공개 버전은 회원가입, 결제, 문의 폼, 자체 분석 도구를 사용하지 않습니다.</p></div></section><section class="section-tight"><div class="container"><div class="info-panel"><div><h3>현재 수집하는 정보</h3><p>사이트 자체 기능은 사용자의 이름, 연락처, 주소 등 개인정보를 서버로 전송하거나 저장하지 않습니다. 검색은 브라우저에서 정적 데이터로 처리됩니다.</p></div><div class="info-aside"><strong>향후 변경 시</strong><br>광고·분석 도구 또는 문의 기능을 추가하는 경우 실제 사용하는 서비스와 수집 항목에 맞춰 이 방침을 반드시 수정해야 합니다.<br><br>시행일: 2026-08-10</div></div></div></section></main>`;
write('/privacy/', layout({ title:'개인정보처리방침 | 버려줘', description:'버려줘 개인정보처리방침입니다.', route:'/privacy/', body:privacyBody }));

const searchIndex = allVerifiedFees.map((r) => ({ regionSlug:r.regionSlug, regionName:r.regionName, item:r.item, itemSlug:r.itemSlug, itemPath:`/seoul/${r.regionSlug}/${r.itemSlug}/`, spec:r.spec, fee:r.fee, terms:[r.item, ...(r.aliases || []), r.spec] }));
fs.writeFileSync(path.join(out, 'search-index.json'), JSON.stringify(searchIndex, null, 2), 'utf8');

fs.mkdirSync(path.join(out, 'assets'), { recursive: true });
fs.copyFileSync(path.join(root, 'src/assets/styles.css'), path.join(out, 'assets/styles.css'));
fs.copyFileSync(path.join(root, 'src/assets/app.js'), path.join(out, 'assets/app.js'));
fs.copyFileSync(path.join(root, 'src/assets/favicon.svg'), path.join(out, 'assets/favicon.svg'));
fs.writeFileSync(path.join(out, '.nojekyll'), '', 'utf8');

const sitemapRoutes = ['/', '/seoul/', '/data-policy/', '/privacy/'];
for (const region of verifiedRegions) {
  sitemapRoutes.push(`/seoul/${region.slug}/`);
  for (const item of uniqueItems(regionFees.get(region.slug))) sitemapRoutes.push(`/seoul/${region.slug}/${item.itemSlug}/`);
}
const sitemap = `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${sitemapRoutes.map((route) => `  <url><loc>${esc(absolute(route))}</loc><lastmod>${today}</lastmod></url>`).join('\n')}\n</urlset>\n`;
fs.writeFileSync(path.join(out, 'sitemap.xml'), sitemap, 'utf8');
fs.writeFileSync(path.join(out, 'robots.txt'), `User-agent: *\nAllow: /\n\nSitemap: ${absolute('/sitemap.xml')}\n`, 'utf8');

const notFound = layout({ title:'페이지를 찾을 수 없어요 | 버려줘', description:'요청한 페이지를 찾을 수 없습니다.', route:'/404.html', robots:'noindex,nofollow', body:`<main id="main"><section class="page-hero"><div class="container"><div class="eyebrow">404</div><h1>이 페이지는<br>잘못 버려졌나 봐요.</h1><p class="page-subtitle">주소를 다시 확인하거나 홈에서 지역과 품목을 검색해주세요.</p><p style="margin-top:24px"><a class="cta-button" style="background:var(--brand);color:#fff" href="${href('/')}">홈으로 돌아가기</a></p></div></section></main>` });
fs.writeFileSync(path.join(out, '404.html'), notFound, 'utf8');

console.log(`✓ build 완료: ${sitemapRoutes.length}개 색인 대상 URL`);
console.log(`  SITE_URL=${siteUrl}`);
console.log(`  BASE_PATH=${basePath || '(root)'}`);
