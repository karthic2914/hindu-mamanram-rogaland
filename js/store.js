(function () {
  const PRASADAM = [
    { id: "vadai", ta: "வடை", en: "Vadai" },
    { id: "pongal", ta: "சர்க்கரை பொங்கல்", en: "Sakkarai pongal" },
    { id: "payasam", ta: "பாயசம்", en: "Payasam" },
    { id: "sundal", ta: "சுண்டல்", en: "Sundal" },
    { id: "kesari", ta: "கேசரி", en: "Kesari" },
    { id: "laddu", ta: "லட்டு", en: "Laddu" },
    { id: "curd", ta: "தயிர்சாதம்", en: "Curd rice" },
    { id: "puli", ta: "புளிசாதம்", en: "Tamarind rice" },
    { id: "fruit", ta: "பழம்", en: "Fruit" },
    { id: "sweet", ta: "இனிப்பு", en: "Sweet" }
  ];

  function read(key, fallback) {
    try {
      const value = JSON.parse(localStorage.getItem(key) || "");
      return value == null ? fallback : value;
    } catch (error) {
      return fallback;
    }
  }

  function write(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
  }

  function esc(value) {
    return String(value ?? "").replace(/[&<>"']/g, (ch) => ({
      "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
    }[ch]));
  }

  function safeUrl(value) {
    try {
      const url = new URL(value);
      if (url.protocol === "http:" || url.protocol === "https:") return url.href;
    } catch (error) {
      return "";
    }
    return "";
  }

  function poojaList() {
    const saved = read("hm-pooja", {});
    const server = window.HM_SERVER || {};
    const remoteAll = server.pooja || {};
    const removed = new Set(server.removed || []);
    const extras = Array.isArray(server.extra) ? server.extra : [];
    const items = window.HM_POOJA.filter((item) => !removed.has(item.id)).concat(extras);
    return items.map((item) => {
      if (item.closed) return item;
      const over = saved[item.id] || {};
      const remote = remoteAll[item.id] || {};
      return {
        ...item,
        date: remote.date || over.date || item.date,
        name: remote.name || over.name || item.name,
        sponsor: remote.sponsor != null ? remote.sponsor : (over.sponsor == null ? item.sponsor : over.sponsor),
        deity: remote.deity || item.deity || "ganesha",
        common: remote.common != null ? !!remote.common : !!item.common,
        special: remote.special != null ? !!remote.special : !!item.special,
        prasadam: over.prasadam || ""
      };
    });
  }

  function savePooja(id, fields) {
    const saved = read("hm-pooja", {});
    saved[id] = { ...(saved[id] || {}), ...fields };
    write("hm-pooja", saved);
  }

  function resetPooja() {
    localStorage.removeItem("hm-pooja");
  }

  function contacts() {
    const list = read("hm-contacts", []);
    return Array.isArray(list) ? list : [];
  }

  function saveContacts(list) {
    write("hm-contacts", list);
  }

  function albums() {
    const list = read("hm-albums", []);
    return Array.isArray(list) ? list : [];
  }

  function saveAlbums(list) {
    write("hm-albums", list);
  }

  function memberUrl() {
    return safeUrl(localStorage.getItem("hm-member-url") || "");
  }

  function setMemberUrl(value) {
    const url = safeUrl(value);
    if (url) localStorage.setItem("hm-member-url", url);
    else localStorage.removeItem("hm-member-url");
    return url;
  }

  function groupEmail() {
    return localStorage.getItem("hm-group-email") || "";
  }

  function setGroupEmail(value) {
    const email = String(value || "").trim();
    if (email) localStorage.setItem("hm-group-email", email);
    else localStorage.removeItem("hm-group-email");
  }

  function prasadamLabel(id) {
    const item = PRASADAM.find((row) => row.id === id);
    if (!item) return "";
    return lang() === "en" ? item.en : item.ta;
  }

  function formatDate(iso) {
    const parts = String(iso || "").split("-");
    if (parts.length !== 3) return iso || "";
    return `${parts[2]}.${parts[1]}.${parts[0]}`;
  }

  function message(event) {
    const lines = [
      "இந்து மாமன்றம் ரூகலாண்ட்",
      "",
      `பூஜை: ${event.name}`,
      `நாள்: ${formatDate(event.date)}`,
      "நேரம்: 18:30",
      "இடம்: Varatun Gård, Varatun hagen, Sandnes"
    ];
    const food = PRASADAM.find((row) => row.id === event.prasadam);
    if (food) lines.push(`பிரசாதம்: ${food.ta}`);
    if (event.sponsor) lines.push(`உபயம்: ${event.sponsor}`);
    lines.push("", "அன்புடன்", "இந்து மாமன்றம் ரூகலாண்ட்", "hindu@mamanram.no");
    return lines.join("\n");
  }

  function mailPeople() {
    return contacts().filter((person) => /\S+@\S+\.\S+/.test(person.email || ""));
  }

  function mailHref(event, people) {
    const bcc = people.map((person) => person.email.trim()).join(";");
    const subject = encodeURIComponent(`பூஜை அறிவிப்பு — ${event.name}`);
    const body = encodeURIComponent(message(event));
    return `mailto:hindu@mamanram.no?bcc=${bcc}&subject=${subject}&body=${body}`;
  }

  function openMail(id, batchIndex) {
    const event = poojaList().find((item) => item.id === id && !item.closed);
    if (!event) return { ok: false, reason: "missing" };
    const people = mailPeople();
    if (!people.length) return { ok: false, reason: "no-mail" };
    const size = 15;
    const batches = [];
    for (let i = 0; i < people.length; i += size) batches.push(people.slice(i, i + size));
    const index = Math.min(batchIndex || 0, batches.length - 1);
    window.location.href = mailHref(event, batches[index]);
    return {
      ok: true,
      batch: index + 1,
      batches: batches.length,
      sent: batches[index].length,
      missing: contacts().length - people.length
    };
  }

  function openGroup(id) {
    const event = poojaList().find((item) => item.id === id && !item.closed);
    const email = groupEmail().trim();
    if (!event) return { ok: false, reason: "missing" };
    if (!/^\S+@\S+\.\S+$/.test(email)) return { ok: false, reason: "no-group" };
    const subject = `பூஜை அறிவிப்பு — ${event.name}`;
    window.location.href = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(message(event))}`;
    return { ok: true };
  }

  function openWhatsApp(id) {
    const event = poojaList().find((item) => item.id === id && !item.closed);
    if (!event) return { ok: false, reason: "missing" };
    window.open(`https://wa.me/?text=${encodeURIComponent(message(event))}`, "_blank", "noopener");
    return { ok: true };
  }

  function db() {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open("hm-committee", 1);
      request.onupgradeneeded = () => request.result.createObjectStore("photos", { keyPath: "id" });
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  function photosFor(poojaId) {
    return db().then((database) => new Promise((resolve, reject) => {
      const request = database.transaction("photos").objectStore("photos").getAll();
      request.onsuccess = () => resolve(request.result.filter((photo) => photo.poojaId === poojaId));
      request.onerror = () => reject(request.error);
    }));
  }

  function allPhotos() {
    return db().then((database) => new Promise((resolve, reject) => {
      const request = database.transaction("photos").objectStore("photos").getAll();
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    }));
  }

  function shrink(file) {
    return new Promise((resolve, reject) => {
      const image = new Image();
      const url = URL.createObjectURL(file);
      image.onload = () => {
        const max = 1280;
        const scale = Math.min(1, max / Math.max(image.width, image.height));
        const canvas = document.createElement("canvas");
        canvas.width = Math.round(image.width * scale);
        canvas.height = Math.round(image.height * scale);
        canvas.getContext("2d").drawImage(image, 0, 0, canvas.width, canvas.height);
        URL.revokeObjectURL(url);
        resolve(canvas.toDataURL("image/jpeg", 0.82));
      };
      image.onerror = () => {
        URL.revokeObjectURL(url);
        reject(new Error("image"));
      };
      image.src = url;
    });
  }

  function addPhoto(poojaId, file) {
    return photosFor(poojaId).then((list) => {
      if (list.length >= 12) throw new Error("full");
      return shrink(file).then((dataUrl) => db().then((database) => new Promise((resolve, reject) => {
        const photo = { id: `${poojaId}-${Date.now()}`, poojaId, name: file.name, dataUrl };
        const request = database.transaction("photos", "readwrite").objectStore("photos").add(photo);
        request.onsuccess = () => resolve(photo);
        request.onerror = () => reject(request.error);
      })));
    });
  }

  function removePhoto(id) {
    return db().then((database) => new Promise((resolve, reject) => {
      const request = database.transaction("photos", "readwrite").objectStore("photos").delete(id);
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    }));
  }

  window.HM = {
    PRASADAM, esc, safeUrl, poojaList, savePooja, resetPooja,
    contacts, saveContacts, albums, saveAlbums,
    memberUrl, setMemberUrl, groupEmail, setGroupEmail,
    prasadamLabel, formatDate, message, openMail, openGroup, openWhatsApp,
    photosFor, allPhotos, addPhoto, removePhoto
  };
})();
