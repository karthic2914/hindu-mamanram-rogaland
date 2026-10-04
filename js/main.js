const EN = {
  "brand.name": "Hindu Mamanram", "brand.place": "Rogaland", "nav.home": "Home", "nav.spirit": "Spiritual life", "nav.worship": "Worship", "nav.donate": "Donations", "nav.services": "Our services", "nav.wedding": "Marriage service", "nav.funeral": "Funeral service", "nav.media": "Multimedia", "nav.music": "Music", "nav.radio": "Devotional radio", "nav.photos": "Photographs", "nav.contact": "Contact", "search.label": "Search", "menu.label": "Menu", "nav.desk": "Committee"
};

const PAGES = [
  { href:"index.html",page:"home",ta:"வாசல்",en:"Home",taBlurb:"இந்து மாமன்றம் ரூகலாண்ட்",enBlurb:"Hindu Mamanram Rogaland" },
  { href:"worship.html",page:"worship",ta:"இறைவழிபாடு",en:"Worship",taBlurb:"2026 பூஜை நாட்கள்",enBlurb:"2026 pooja dates" },
  { href:"donate.html",page:"donate",ta:"நன்கொடை",en:"Donations",taBlurb:"கோவில் நிதி மற்றும் கிராஸ்ரூட்",enBlurb:"Temple fund and Grasrotandelen" },
  { href:"wedding.html",page:"wedding",ta:"திருமணசேவை",en:"Marriage service",taBlurb:"இந்து முறை திருமணம்",enBlurb:"Hindu marriage rites" },
  { href:"funeral.html",page:"funeral",ta:"அந்தியகாலசேவை",en:"Funeral service",taBlurb:"அந்தியக்கிரிகைகள்",enBlurb:"Last rites" },
  { href:"gallery.html",page:"gallery",ta:"நிழற்படங்கள்",en:"Photographs",taBlurb:"வரலட்சுமி பூஜை",enBlurb:"Varalakshmi pooja" },
  { href:"radio.html",page:"radio",ta:"பக்தி வானொலி",en:"Devotional radio",taBlurb:"பக்திப் பாடல்கள்",enBlurb:"Devotional songs" },
  { href:"contact.html",page:"contact",ta:"தொடர்பு",en:"Contact",taBlurb:"Sandnes, Norway",enBlurb:"Sandnes, Norway" },
  { href:"committee.html",page:"committee",ta:"நிர்வாகம்",en:"Committee",taBlurb:"தொடர்பு, பிரசாதம், பூஜை நாள்",enBlurb:"Contacts, prasadam, pooja dates" }
];
const MARK=`<img class="brand-mark" src="assets/hm-logo.jpg" alt="">`;
function lang(){return localStorage.getItem("hm-lang")==="en"?"en":"ta"}
function t(key,fallback){return lang()==="en"&&EN[key]?EN[key]:fallback}
function header(){
 const page=document.body.dataset.page,active=n=>page===n?" is-active":"",spirit=page==="worship"||page==="donate",services=page==="wedding"||page==="funeral",media=page==="gallery"||page==="radio";
 const mi=(icon,html)=>`<i class="fa-solid ${icon} menu-icon" aria-hidden="true"></i><span>${html}</span>`;
 return `<a class="skip" href="#main">${lang()==="en"?"Skip to content":"உள்ளடக்கத்திற்குச் செல்"}</a>
 <a class="brand" href="index.html">${MARK}<span class="brand-text"><strong>${t("brand.name","இந்து மாமன்றம்")}</strong><small>${t("brand.place","ரூகலாண்ட்")}</small></span></a>
 <button class="nav-toggle" type="button" aria-expanded="false" aria-label="${t("menu.label","பட்டி")}"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 5h14M3 10h14M3 15h14"/></svg></button>
 <nav class="nav" id="site-nav">
 <div class="nav-item"><a class="nav-link${active("home")}" href="index.html">${t("nav.home","வாசல்")}</a></div>
 <div class="nav-item"><button class="nav-parent${spirit?" is-active":""}" type="button">${t("nav.spirit","ஆன்மீகம்")}</button><div class="menu">
 <a href="worship.html">${mi("fa-hands-praying",t("nav.worship","இறைவழிபாடு"))}</a>
 <a href="donate.html">${mi("fa-hand-holding-heart",t("nav.donate","நன்கொடை"))}</a></div></div>
 <div class="nav-item"><button class="nav-parent${services?" is-active":""}" type="button">${t("nav.services","எமது சேவை")}</button><div class="menu">
 <a href="wedding.html">${mi("fa-ring",t("nav.wedding","திருமணசேவை"))}</a>
 <a href="funeral.html">${mi("fa-hands-praying",t("nav.funeral","அந்தியகாலசேவை"))}</a></div></div>
 <div class="nav-item"><button class="nav-parent${media?" is-active":""}" type="button">${t("nav.media","பல்லூடகம்")}</button><div class="menu">
 <span class="group-label"><i class="fa-solid fa-music menu-label-icon" aria-hidden="true"></i>${t("nav.music","இசை")}</span>
 <a href="radio.html">${mi("fa-radio",t("nav.radio","பக்தி வானொலி"))}</a>
 <span class="group-label"><i class="fa-solid fa-camera menu-label-icon" aria-hidden="true"></i>${t("nav.photos","நிழற்படங்கள்")}</span>
 <a href="gallery.html">${mi("fa-images",t("nav.photos","நிழற்படங்கள்"))}</a>
 <a href="gallery.html#y2019">${mi("fa-calendar-days",lang()==="en"?"Varalakshmi 2019":"வரலட்சுமி 2019")}</a>
 <a href="gallery.html#y2022">${mi("fa-calendar-days",lang()==="en"?"Varalakshmi 2022":"வரலட்சுமி 2022")}</a>
 <a href="gallery.html#y2023">${mi("fa-calendar-days",lang()==="en"?"Varalakshmi 2023":"வரலட்சுமி 2023")}</a>
 <a href="gallery.html#y2024">${mi("fa-calendar-days",lang()==="en"?"Varalakshmi 2024":"வரலட்சுமி 2024")}</a></div></div>
 <div class="nav-item"><a class="nav-link${active("contact")}" href="contact.html">${t("nav.contact","தொடர்பு")}</a></div></nav>
 <div class="header-tools"><button class="icon-btn" type="button" id="search-open" aria-label="${t("search.label","தேடல்")}"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg></button><div class="lang" role="group" aria-label="Language"><button type="button" data-set-lang="ta" class="${lang()==="ta"?"is-active":""}">த</button><button type="button" data-set-lang="en" class="${lang()==="en"?"is-active":""}">EN</button></div></div>`;
}
function footer(){return `<div class="wrap foot-grid"><div><h3>${t("brand.name","இந்து மாமன்றம்")} ${t("brand.place","ரூகலாண்ட்")}</h3></div><div><h3>Varatun Gård</h3><p>Varatun hagen<br>Sandnes</p></div><div><h3>${t("nav.contact","தொடர்பு")}</h3><ul><li><a href="mailto:hindu@mamanram.no">hindu@mamanram.no</a></li><li><a href="contact.html">${t("nav.contact","தொடர்பு")}</a></li><li><a href="committee.html">${t("nav.desk","நிர்வாகம்")}</a></li></ul></div></div><p class="credit">developed by <a href="https://www.devndespro.com" target="_blank" rel="noopener">www.devndespro.com</a></p>`}
function applyPageLanguage(){document.documentElement.lang=lang()==="en"?"en":"ta";document.querySelectorAll("[data-i18n]").forEach(el=>{if(!el.dataset.ta)el.dataset.ta=el.textContent.trim();const key=el.dataset.i18n;el.textContent=lang()==="en"&&EN[key]?EN[key]:el.dataset.ta});const title=lang()==="en"?document.body.dataset.titleEn:document.body.dataset.titleTa;if(title)document.title=title}
function renderChrome(){const h=document.getElementById("site-header"),f=document.getElementById("site-footer");if(h)h.innerHTML=header();if(f)f.innerHTML=footer();bindChrome()}
function bindChrome(){document.querySelectorAll("[data-set-lang]").forEach(b=>b.addEventListener("click",()=>{localStorage.setItem("hm-lang",b.dataset.setLang);location.reload()}));const toggle=document.querySelector(".nav-toggle"),nav=document.getElementById("site-nav");if(toggle&&nav)toggle.addEventListener("click",()=>{const open=nav.classList.toggle("is-open");toggle.setAttribute("aria-expanded",String(open))});document.querySelectorAll(".nav-parent").forEach(btn=>btn.addEventListener("click",()=>{const item=btn.closest(".nav-item");document.querySelectorAll(".nav-item.is-open").forEach(x=>{if(x!==item)x.classList.remove("is-open")});item.classList.toggle("is-open")}));}
document.addEventListener("DOMContentLoaded",()=>{applyPageLanguage();renderChrome()});