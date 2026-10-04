(function () {
  if (!window.HM) return;

  function tr(ta, en) {
    return lang() === "en" ? en : ta;
  }

  function applyLabels() {
    document.querySelectorAll("[data-ta]").forEach((el) => {
      el.textContent = tr(el.dataset.ta, el.dataset.en);
    });
    document.querySelectorAll("[data-ph-ta]").forEach((el) => {
      el.placeholder = tr(el.dataset.phTa, el.dataset.phEn);
    });
  }

  function status(text) {
    const el = document.getElementById("desk-status");
    if (el) el.textContent = text || "";
  }

  function renderSend() {
    const select = document.getElementById("send-pooja");
    const current = select.value;
    const events = HM.poojaList().filter((item) => !item.closed);
    select.innerHTML = events.map((item) => `<option value="${HM.esc(item.id)}">${HM.esc(HM.formatDate(item.date))} — ${HM.esc(item.name)}</option>`).join("");
    if (events.some((item) => item.id === current)) select.value = current;
    else {
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const upcoming = events.find((item) => new Date(`${item.date}T00:00:00`) >= today);
      if (upcoming) select.value = upcoming.id;
    }
    document.getElementById("group-email").value = HM.groupEmail();
    renderPreview();
  }

  function selectedEvent() {
    return HM.poojaList().find((item) => item.id === document.getElementById("send-pooja").value);
  }

  function renderPreview() {
    const event = selectedEvent();
    const preview = document.getElementById("send-preview");
    const food = document.getElementById("send-prasadam");
    if (!event) return;
    food.hidden = !event.common;
    food.innerHTML = event.common ? `<span>${tr("பிரசாதம்", "Prasadam")}</span><select id="send-food">${["<option value=\"\">" + tr("பிரசாதத்தைத் தேர்ந்தெடுங்கள்", "Choose prasadam") + "</option>"]
      .concat(HM.PRASADAM.map((item) => `<option value="${item.id}"${item.id === event.prasadam ? " selected" : ""}>${tr(item.ta, item.en)}</option>`)).join("")}</select>` : "";
    preview.textContent = HM.message(event);
  }

  let editing = null;

  function renderContacts(filter) {
    const q = (filter || "").trim().toLowerCase();
    const list = HM.contacts().filter((person) => {
      const blob = `${person.name} ${person.phone} ${person.email}`.toLowerCase();
      return !q || blob.includes(q);
    });
    const box = document.getElementById("contact-list");
    document.getElementById("contact-count").textContent = `${HM.contacts().length}`;
    if (!list.length) {
      box.innerHTML = `<p class="empty">${tr("இன்னும் தொடர்பு இல்லை. பெயர், தொலைபேசி, அஞ்சல் என மேலே சேருங்கள்.", "No contacts yet. Add a name, phone, and email above.")}</p>`;
      return;
    }
    box.innerHTML = list.map((person) => `<article class="person">
      <div><strong>${HM.esc(person.name)}</strong><small>${HM.esc(person.phone || "—")}</small></div>
      <div>${HM.esc(person.email || "—")}</div>
      <div class="person-actions">
        <button type="button" data-edit="${HM.esc(person.id)}">${tr("மாற்று", "Edit")}</button>
        <button type="button" data-delete="${HM.esc(person.id)}">${tr("நீக்கு", "Remove")}</button>
      </div>
    </article>`).join("");
  }

  const DEITY = {
    ganesha: ["assets/deity-ganesha.jpg", ""],
    shiva: ["assets/deity-shiva.jpg", ""],
    lakshmi: ["assets/deity-lakshmi.jpg", ""],
    saraswati: ["assets/deity-saraswati.jpg", ""],
    mahalaya: ["assets/deity-mahalaya.jpg", " is-lamp"]
  };

  function deityImg(item) {
    const deity = DEITY[item.deity] || DEITY.ganesha;
    return `<img class="cal-deity${deity[1]}" src="${deity[0]}" alt="">`;
  }

  function parseDate(value) {
    const match = String(value || "").trim().match(/^(\d{1,2})[./-](\d{1,2})[./-](\d{4})$/);
    if (!match) return "";
    const day = match[1].padStart(2, "0");
    const month = match[2].padStart(2, "0");
    const iso = `${match[3]}-${month}-${day}`;
    const check = new Date(`${iso}T00:00:00`);
    if (Number.isNaN(check.getTime()) || check.getDate() !== Number(day) || check.getMonth() + 1 !== Number(month)) return "";
    return iso;
  }

  function renderDates() {
    const box = document.getElementById("date-list");
    box.innerHTML = HM.poojaList().filter((item) => !item.closed).map((item) => `<article class="date-row">
      ${deityImg(item)}
      <input type="text" inputmode="numeric" value="${HM.esc(HM.formatDate(item.date))}" placeholder="30.01.2026" data-field="date" data-id="${HM.esc(item.id)}" aria-label="${tr("நாள்", "Date")}">
      <input type="text" value="${HM.esc(item.name)}" data-field="name" data-id="${HM.esc(item.id)}" aria-label="${tr("பூஜை", "Pooja")}" placeholder="${tr("பூஜை", "Pooja")}">
      <input type="text" value="${HM.esc(item.sponsor)}" data-field="sponsor" data-id="${HM.esc(item.id)}" aria-label="${tr("உபயதாரர்", "Sponsor")}" placeholder="${tr("உபயதாரர்", "Sponsor")}">
    </article>`).join("");
  }

  function renderPhotos() {
    const events = HM.poojaList().filter((item) => item.common);
    const box = document.getElementById("photo-list");
    box.innerHTML = events.map((item) => `<article class="photo-card" id="photo-${HM.esc(item.id)}">
      ${deityImg(item)}
      <div>
        <h3>${HM.esc(HM.formatDate(item.date))} — ${HM.esc(item.name)}</h3>
        <label class="drop-zone" data-drop="${HM.esc(item.id)}">
          <span>${tr("படங்களை இங்கே இடுங்கள்", "Drop photographs here")}</span>
          <input type="file" accept="image/*" multiple data-upload="${HM.esc(item.id)}">
        </label>
        <div class="photo-grid" data-grid="${HM.esc(item.id)}"></div>
      </div>
    </article>`).join("");
    events.forEach((item) => {
      HM.photosFor(item.id).then((photos) => {
        const grid = box.querySelector(`[data-grid="${item.id}"]`);
        if (!grid) return;
        grid.innerHTML = photos.map((photo) => `<figure>
          <img src="${photo.dataUrl}" alt="${HM.esc(photo.name || item.name)}">
          <button type="button" data-drop-photo="${HM.esc(photo.id)}" data-pooja="${HM.esc(item.id)}">${tr("நீக்கு", "Remove")}</button>
        </figure>`).join("");
      }).catch(() => {});
    });
  }

  function renderAlbums() {
    const box = document.getElementById("album-list");
    const list = HM.albums();
    box.innerHTML = list.length ? list.map((album) => `<li>
      <a href="${HM.esc(album.url)}" target="_blank" rel="noopener">${HM.esc(album.title)}</a>
      <button type="button" data-drop-album="${HM.esc(album.id)}">${tr("நீக்கு", "Remove")}</button>
    </li>`).join("") : "";
  }

  function renderMember() {
    const input = document.getElementById("member-url");
    const open = document.getElementById("member-open");
    const url = HM.memberUrl();
    input.value = url;
    open.href = url || "https://drive.google.com/file/d/1r3KylsjU2gi9ltvv-0XzuL-FSpJ1m9DA/view?usp=sharing";
  }

  function render() {
    applyLabels();
    renderSend();
    renderContacts(document.getElementById("contact-search").value);
    renderDates();
    renderPhotos();
    renderAlbums();
    renderMember();
  }

  document.getElementById("send-pooja").addEventListener("change", renderPreview);
  document.getElementById("send-prasadam").addEventListener("change", (event) => {
    const select = event.target.closest("#send-food");
    if (!select) return;
    HM.savePooja(document.getElementById("send-pooja").value, { prasadam: select.value });
    renderPreview();
  });
  document.getElementById("group-email").addEventListener("change", (event) => {
    HM.setGroupEmail(event.target.value);
  });
  document.getElementById("send-mail").addEventListener("click", () => {
    const result = HM.openMail(document.getElementById("send-pooja").value, 0);
    if (!result.ok) {
      status(tr("அஞ்சல் முகவரியுடன் குறைந்தது ஒரு தொடர்பைச் சேருங்கள்.", "Add at least one contact with an email address."));
      return;
    }
    status(result.batches > 1
      ? tr(`கோவில் அஞ்சல் திறக்கப்பட்டது. தொகுதி ${result.batch}/${result.batches}.`, `Temple mail opened. Batch ${result.batch}/${result.batches}.`)
      : tr("கோவில் அஞ்சல் திறக்கப்பட்டது. பெறுநர் பட்டியலைப் பார்த்து அனுப்புங்கள்.", "Temple mail opened. Check the recipients, then send."));
  });
  document.getElementById("send-group").addEventListener("click", () => {
    const result = HM.openGroup(document.getElementById("send-pooja").value);
    status(result.ok
      ? tr("குழு அஞ்சல் திறக்கப்பட்டது.", "Group mail opened.")
      : tr("குழு அஞ்சல் முகவரியை மேலே சேமித்துவிட்டு மீண்டும் அழுத்துங்கள்.", "Save the group email above, then try again."));
  });
  document.getElementById("send-wa").addEventListener("click", () => {
    HM.openWhatsApp(document.getElementById("send-pooja").value);
    status(tr("வாட்ஸ்அப் திறக்கப்பட்டது. இந்து மாமன்றம் குழுவைத் தேர்ந்தெடுத்து அனுப்புங்கள்.", "WhatsApp opened. Choose the Hindu Mamanram group and send."));
  });

  document.getElementById("contact-form").addEventListener("submit", (event) => {
    event.preventDefault();
    const name = document.getElementById("c-name").value.trim();
    const phone = document.getElementById("c-phone").value.trim();
    const email = document.getElementById("c-email").value.trim();
    if (!name || (!phone && !email)) {
      status(tr("பெயரும், தொலைபேசி அல்லது அஞ்சலும் வேண்டும்.", "A name and either a phone or an email are needed."));
      return;
    }
    const list = HM.contacts();
    if (editing) {
      const row = list.find((person) => person.id === editing);
      if (row) {
        row.name = name;
        row.phone = phone;
        row.email = email;
      }
      editing = null;
    } else {
      list.push({ id: `c${Date.now()}`, name, phone, email });
    }
    HM.saveContacts(list);
    event.target.reset();
    document.getElementById("c-save").textContent = tr("சேர்", "Add");
    renderContacts(document.getElementById("contact-search").value);
    status(tr("தொடர்பு சேமிக்கப்பட்டது.", "Contact saved."));
  });

  document.getElementById("contact-search").addEventListener("input", (event) => {
    renderContacts(event.target.value);
  });

  document.getElementById("contact-list").addEventListener("click", (event) => {
    const edit = event.target.closest("[data-edit]");
    const remove = event.target.closest("[data-delete]");
    if (edit) {
      const person = HM.contacts().find((item) => item.id === edit.dataset.edit);
      if (!person) return;
      editing = person.id;
      document.getElementById("c-name").value = person.name;
      document.getElementById("c-phone").value = person.phone || "";
      document.getElementById("c-email").value = person.email || "";
      document.getElementById("c-save").textContent = tr("புதுப்பி", "Update");
      document.getElementById("c-name").focus();
    }
    if (remove) {
      if (remove.dataset.sure !== "1") {
        remove.dataset.sure = "1";
        remove.textContent = tr("உறுதி", "Confirm");
        return;
      }
      HM.saveContacts(HM.contacts().filter((person) => person.id !== remove.dataset.delete));
      renderContacts(document.getElementById("contact-search").value);
    }
  });

  document.getElementById("contact-export").addEventListener("click", () => {
    const blob = new Blob([JSON.stringify(HM.contacts(), null, 2)], { type: "application/json" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "mamanram-contacts.json";
    link.click();
    URL.revokeObjectURL(link.href);
  });

  document.getElementById("contact-import").addEventListener("change", (event) => {
    const file = event.target.files && event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = () => {
      try {
        const incoming = JSON.parse(reader.result);
        if (!Array.isArray(incoming)) throw new Error("format");
        const list = HM.contacts();
        incoming.forEach((person) => {
          if (!person || !person.name) return;
          const email = String(person.email || "").trim();
          if (email && list.some((row) => (row.email || "").toLowerCase() === email.toLowerCase())) return;
          list.push({
            id: `c${Date.now()}-${list.length}`,
            name: String(person.name).trim(),
            phone: String(person.phone || "").trim(),
            email
          });
        });
        HM.saveContacts(list);
        renderContacts("");
        status(tr("தொடர்புகள் சேர்க்கப்பட்டன.", "Contacts added."));
      } catch (error) {
        status(tr("இந்தக் கோப்பைப் படிக்க முடியவில்லை.", "That file could not be read."));
      }
    };
    reader.readAsText(file);
    event.target.value = "";
  });

  document.getElementById("date-list").addEventListener("change", (event) => {
    const input = event.target.closest("[data-field]");
    if (!input) return;
    const value = input.value.trim();
    if (input.dataset.field === "date") {
      const iso = parseDate(value);
      if (!iso) {
        status(tr("நாளை 30.01.2026 என்று எழுதுங்கள்.", "Write the date as 30.01.2026."));
        return;
      }
      HM.savePooja(input.dataset.id, { date: iso });
      input.value = HM.formatDate(iso);
    } else {
      HM.savePooja(input.dataset.id, { [input.dataset.field]: value });
    }
    renderSend();
    renderPhotos();
    status(tr("பூஜை நாள் சேமிக்கப்பட்டது.", "Pooja date saved."));
  });

  document.getElementById("date-reset").addEventListener("click", () => {
    HM.resetPooja();
    render();
    status(tr("வெளியிட்ட அட்டவணை திரும்ப வைக்கப்பட்டது.", "The published calendar is restored."));
  });

  function storePhotos(id, files) {
    const images = [...files].filter((file) => file.type.startsWith("image/"));
    if (!images.length) return;
    Promise.all(images.map((file) => HM.addPhoto(id, file))).then(() => {
      renderPhotos();
      status(tr("படம் சேர்க்கப்பட்டது.", "Photo added."));
    }).catch((error) => {
      status(error && error.message === "full"
        ? tr("ஒரு பூஜைக்கு 12 படங்கள் வரை.", "Up to 12 photos for one pooja.")
        : tr("படத்தைச் சேர்க்க முடியவில்லை.", "The photo could not be added."));
    });
  }

  document.getElementById("photo-list").addEventListener("change", (event) => {
    const input = event.target.closest("[data-upload]");
    if (!input || !input.files) return;
    storePhotos(input.dataset.upload, input.files);
    input.value = "";
  });

  document.getElementById("photo-list").addEventListener("dragover", (event) => {
    if (!event.target.closest("[data-drop]")) return;
    event.preventDefault();
    event.target.closest("[data-drop]").classList.add("is-over");
  });

  document.getElementById("photo-list").addEventListener("dragleave", (event) => {
    const zone = event.target.closest("[data-drop]");
    if (zone) zone.classList.remove("is-over");
  });

  document.getElementById("photo-list").addEventListener("drop", (event) => {
    const zone = event.target.closest("[data-drop]");
    if (!zone) return;
    event.preventDefault();
    zone.classList.remove("is-over");
    storePhotos(zone.dataset.drop, event.dataTransfer.files);
  });

  document.getElementById("photo-list").addEventListener("click", (event) => {
    const button = event.target.closest("[data-drop-photo]");
    if (!button) return;
    HM.removePhoto(button.dataset.dropPhoto).then(renderPhotos);
  });

  document.getElementById("album-form").addEventListener("submit", (event) => {
    event.preventDefault();
    const title = document.getElementById("album-title").value.trim();
    const url = HM.safeUrl(document.getElementById("album-url").value.trim());
    if (!title || !url) {
      status(tr("ஆல்பப் பெயரும் இணைப்பும் வேண்டும்.", "An album name and a link are needed."));
      return;
    }
    const list = HM.albums();
    list.push({ id: `a${Date.now()}`, title, url });
    HM.saveAlbums(list);
    event.target.reset();
    renderAlbums();
  });

  document.getElementById("album-list").addEventListener("click", (event) => {
    const button = event.target.closest("[data-drop-album]");
    if (!button) return;
    HM.saveAlbums(HM.albums().filter((album) => album.id !== button.dataset.dropAlbum));
    renderAlbums();
  });

  document.getElementById("member-form").addEventListener("submit", (event) => {
    event.preventDefault();
    const url = HM.setMemberUrl(document.getElementById("member-url").value.trim());
    renderMember();
    status(url
      ? tr("ரகுவின் படிவ இணைப்பு வைக்கப்பட்டது.", "Ragu's form link is saved.")
      : tr("இணைப்பு காலியாக உள்ளது. இருக்கும் படிவம் தொடரும்.", "The link is empty. The existing form stays."));
  });

  document.addEventListener("hm-lang", render);
  render();
})();
