const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];

const normalize = (value = '') => value.toLowerCase().replace(/\s+/g, '').replace(/[()·,.\-_/]/g, '');
const money = (value) => new Intl.NumberFormat('ko-KR').format(value) + '원';

function initSearchForm() {
  const form = $('[data-search-form]');
  if (!form) return;
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const region = form.elements.region?.value;
    const item = form.elements.item?.value?.trim();
    if (!region) {
      form.elements.region?.focus();
      showToast('지역을 먼저 골라주세요.');
      return;
    }
    const base = document.documentElement.dataset.basePath || '';
    if (!item) {
      location.href = `${base}/seoul/${region}/`;
      return;
    }
    try {
      const response = await fetch(`${base}/search-index.json`);
      const index = await response.json();
      const q = normalize(item);
      const exact = index.find((entry) => entry.regionSlug === region && entry.terms.some((term) => normalize(term) === q));
      if (exact?.itemPath) {
        location.href = `${base}${exact.itemPath}`;
      } else {
        location.href = `${base}/search/?region=${encodeURIComponent(region)}&q=${encodeURIComponent(item)}`;
      }
    } catch {
      location.href = `${base}/search/?region=${encodeURIComponent(region)}&q=${encodeURIComponent(item)}`;
    }
  });
}

async function initSearchResults() {
  const mount = $('[data-search-results]');
  if (!mount) return;
  const params = new URLSearchParams(location.search);
  const qRaw = params.get('q') || '';
  const region = params.get('region') || '';
  const q = normalize(qRaw);
  const queryLabel = $('[data-query-label]');
  if (queryLabel) queryLabel.textContent = qRaw ? `“${qRaw}” 검색 결과` : '검색 결과';
  if (!q) {
    mount.innerHTML = '<div class="empty-state">검색할 물건 이름을 입력해주세요.</div>';
    return;
  }
  try {
    const base = document.documentElement.dataset.basePath || '';
    const response = await fetch(`${base}/search-index.json`);
    const index = await response.json();
    const results = index
      .filter((entry) => (!region || entry.regionSlug === region) && entry.terms.some((term) => normalize(term).includes(q) || q.includes(normalize(term))))
      .slice(0, 30);
    if (!results.length) {
      mount.innerHTML = '<div class="empty-state"><strong>딱 맞는 품목을 찾지 못했어요.</strong><br><br>비슷한 이름으로 다시 검색하거나 지역 페이지에서 전체 품목을 확인해보세요.</div>';
      return;
    }
    mount.innerHTML = results.map((entry) => `
      <a class="result-card" href="${base}${entry.itemPath}">
        <div><h3>${escapeHtml(entry.regionName)} · ${escapeHtml(entry.item)}</h3><p>${escapeHtml(entry.spec)}</p></div>
        <div class="result-price">${money(entry.fee)}</div>
      </a>`).join('');
  } catch {
    mount.innerHTML = '<div class="empty-state">검색 데이터를 불러오지 못했습니다. 잠시 후 다시 시도해주세요.</div>';
  }
}

function initFeeFilter() {
  const input = $('[data-fee-filter]');
  if (!input) return;
  const cards = $$('[data-fee-card]');
  const blocks = $$('[data-category-block]');
  input.addEventListener('input', () => {
    const q = normalize(input.value);
    cards.forEach((card) => {
      const visible = !q || normalize(card.dataset.searchText || '').includes(q);
      card.hidden = !visible;
    });
    blocks.forEach((block) => {
      const anyVisible = $$('[data-fee-card]', block).some((card) => !card.hidden);
      block.hidden = !anyVisible;
    });
  });
}

function showToast(message) {
  let toast = $('.toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.className = 'toast';
    document.body.appendChild(toast);
  }
  toast.textContent = message;
  toast.classList.add('show');
  clearTimeout(window.__toastTimer);
  window.__toastTimer = setTimeout(() => toast.classList.remove('show'), 1800);
}

function escapeHtml(value = '') {
  return value.replace(/[&<>'"]/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));
}

initSearchForm();
initSearchResults();
initFeeFilter();
