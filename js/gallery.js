(function () {
  if (!window.HM) return;

  function tr(ta, en) {
    return lang() === "en" ? en : ta;
  }

  function render() {
    const albums = document.getElementById("extra-albums");
    const photos = document.getElementById("extra-photos");
    if (!albums || !photos) return;
    const list = HM.albums();
    albums.innerHTML = list.map((album) => `<a class="year" href="${HM.esc(album.url)}" target="_blank" rel="noopener">
      <span>${tr("ஆல்பத்தைத் திறக்க", "Open album")}</span>
      <strong>${HM.esc(album.title)}</strong>
    </a>`).join("");
    HM.allPhotos().then((items) => {
      const events = HM.poojaList().filter((item) => !item.closed);
      photos.innerHTML = items.map((photo) => {
        const event = events.find((item) => item.id === photo.poojaId);
        const caption = event ? `${HM.formatDate(event.date)} — ${event.name}` : "";
        return `<figure><img src="${photo.dataUrl}" alt="${HM.esc(caption)}"><figcaption>${HM.esc(caption)}</figcaption></figure>`;
      }).join("");
      document.getElementById("extra-photos-title").hidden = !items.length;
    }).catch(() => {});
  }

  document.addEventListener("hm-lang", render);
  render();
})();
