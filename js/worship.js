(function () {
  const root = document.getElementById("pooja-year");
  if (!root || !window.HM) return;

  const DEITY = {
    ganesha: {
      src: "assets/deity-ganesha.jpg",
      ta: "விநாயகர்",
      en: "Vinayagar"
    },
    shiva: {
      src: "assets/deity-shiva.jpg",
      ta: "சிவன்",
      en: "Shiva"
    },
    lakshmi: {
      src: "assets/deity-lakshmi.jpg",
      ta: "வரலட்சுமி",
      en: "Varalakshmi"
    },
    saraswati: {
      src: "assets/deity-saraswati.jpg",
      ta: "சரஸ்வதி",
      en: "Saraswati"
    },
    mahalaya: {
      src: "assets/deity-mahalaya.jpg",
      ta: "மஹாளய விளக்கு",
      en: "Mahalaya lamp",
      lamp: true
    }
  };

  const MONTH_TA = [
    "ஜனவரி",
    "பெப்ரவரி",
    "மார்ச்",
    "ஏப்ரல்",
    "மே",
    "ஜூன்",
    "ஜூலை",
    "ஆகஸ்ட்",
    "செப்டம்பர்",
    "அக்டோபர்",
    "நவம்பர்",
    "டிசம்பர்"
  ];

  const MONTH_EN = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December"
  ];

  const WEEK_TA = [
    "ஞாயிறு",
    "திங்கள்",
    "செவ்வாய்",
    "புதன்",
    "வியாழன்",
    "வெள்ளி",
    "சனி"
  ];

  const WEEK_EN = [
    "Sunday",
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday"
  ];

  const PERSON = `
    <svg viewBox="0 0 24 24" fill="none"
      stroke="currentColor" stroke-width="1.8"
      aria-hidden="true">
      <circle cx="12" cy="8" r="3"/>
      <path d="M5 19c1.5-3 4-4.5 7-4.5S17.5 16 19 19"/>
    </svg>
  `;

  const CLOCK = `
    <svg viewBox="0 0 24 24" fill="none"
      stroke="currentColor" stroke-width="1.8"
      aria-hidden="true">
      <circle cx="12" cy="12" r="8"/>
      <path d="M12 8v5l3 2"/>
    </svg>
  `;

  const PIN = `
    <svg viewBox="0 0 24 24" fill="none"
      stroke="currentColor" stroke-width="1.8"
      aria-hidden="true">
      <path d="M12 21s7-6 7-11a7 7 0 1 0-14 0c0 5 7 11 7 11z"/>
      <circle cx="12" cy="10" r="2.2"/>
    </svg>
  `;

  function lang() {
    return document.documentElement.lang === "en" ? "en" : "ta";
  }

  function tr(ta, en) {
    return lang() === "en" ? en : ta;
  }

  function monthName(index) {
    return lang() === "en"
      ? MONTH_EN[index]
      : MONTH_TA[index];
  }

  function weekday(iso) {
    const names = lang() === "en" ? WEEK_EN : WEEK_TA;
    const date = new Date(`${iso}T00:00:00`);

    return names[date.getDay()];
  }

  function prasadamField(event) {
    const options = [
      `<option value="">
        ${tr(
          "பிரசாதத்தைத் தேர்ந்தெடுங்கள்",
          "Choose prasadam"
        )}
      </option>`
    ];

    HM.PRASADAM.forEach((item) => {
      const selected =
        item.id === event.prasadam ? " selected" : "";

      options.push(`
        <option
          value="${HM.esc(item.id)}"
          ${selected}
        >
          ${HM.esc(tr(item.ta, item.en))}
        </option>
      `);
    });

    return `
      <label class="prasadam-label">

        <span>
          ${tr("பிரசாதம்", "Prasadam")}
        </span>

        <select
          class="prasadam"
          data-prasadam="${HM.esc(event.id)}"
        >
          ${options.join("")}
        </select>

      </label>
    `;
  }

  function actions(event) {
    if (!event.common) return "";

    return `
      <div class="cal-actions">

        <button
          type="button"
          data-send="mail"
          data-id="${HM.esc(event.id)}"
        >
          ${tr("கோவில் அஞ்சல்", "Temple mail")}
        </button>

        <button
          type="button"
          data-send="group"
          data-id="${HM.esc(event.id)}"
        >
          ${tr("குழு அஞ்சல்", "Group mail")}
        </button>

        <button
          type="button"
          data-send="whatsapp"
          data-id="${HM.esc(event.id)}"
        >
          ${tr("வாட்ஸ்அப் குழு", "WhatsApp group")}
        </button>

      </div>
    `;
  }

  function eventHtml(event) {
    const deity = DEITY[event.deity] || DEITY.ganesha;

    const day = Number(event.date.slice(8));

    return `
      <article
        class="pooja-event${event.special ? " is-special" : ""}"
        data-date="${HM.esc(event.date)}"
        data-id="${HM.esc(event.id)}"
      >

        <div class="pooja-date">

          <strong>${day}</strong>

          <span>
            ${weekday(event.date)}
          </span>

        </div>


        <div class="pooja-deity">

          <img
            class="${deity.lamp ? "is-lamp" : ""}"
            src="${deity.src}"
            alt="${HM.esc(tr(deity.ta, deity.en))}"
          >

        </div>


        <div class="pooja-info">

          <span class="next-badge">
            ${tr("அடுத்த பூஜை", "Next pooja")}
          </span>

          <h3 class="pooja-name">
            ${HM.esc(event.name)}
          </h3>

          <p class="pooja-sponsor">

            <span class="pooja-meta-icon">
              ${PERSON}
            </span>

            <span>
              ${tr("உபயதாரர்", "Sponsor")}:
              ${HM.esc(event.sponsor)}
            </span>

          </p>

        </div>


        <div class="pooja-event-meta">

          <span>

            <span class="pooja-meta-icon">
              ${CLOCK}
            </span>

            <strong>18:30</strong>

          </span>

          <span>

            <span class="pooja-meta-icon">
              ${PIN}
            </span>

            Varatun Gård

          </span>

        </div>


        <div class="pooja-action">

          <button
            type="button"
            class="btn-detail"
            data-detail
            aria-expanded="false"
          >
            ${tr("விவரம் பார்க்க", "View details")}

            <span aria-hidden="true">
              →
            </span>
          </button>

        </div>


        <div class="pooja-detail">

          <div class="pooja-detail-inner">

            <div class="detail-venue">

              <span class="pooja-meta-icon">
                ${PIN}
              </span>

              <div>

                <strong>
                  ${tr(
                    "பூஜை நடைபெறும் இடம்",
                    "Pooja venue"
                  )}
                </strong>

                <p>
                  Varatun Gård,
                  Varatun hagen,
                  Sandnes, Norway
                </p>

              </div>

            </div>


            ${
              event.common
                ? prasadamField(event)
                : ""
            }


            <div
              class="cal-photos"
              data-photos="${HM.esc(event.id)}"
            ></div>


            ${actions(event)}

          </div>

        </div>

      </article>
    `;
  }

  function closedHtml() {
    return `
      <article class="pooja-event pooja-closed">

        <div class="pooja-date">

          <strong>7</strong>

          <span>
            ${tr("செவ்வாய்", "Tuesday")}
          </span>

        </div>


        <div class="pooja-closed-icon">
          —
        </div>


        <div class="pooja-info">

          <h3 class="pooja-name">
            ${tr("விடுமுறை", "Holiday")}
          </h3>

          <p class="pooja-sponsor">
            ${tr(
              "விடுமுறையில் மண்டபம் மூடப்படும்",
              "The hall is closed for the holiday."
            )}
          </p>

        </div>

      </article>
    `;
  }

  function render() {
    const list = HM.poojaList();

    const events = list
      .filter((item) => !item.closed)
      .sort((a, b) => a.date.localeCompare(b.date));

    const hasClosed =
      list.some((item) => item.closed);

    const groups = {};

    events.forEach((event) => {
      const month =
        Number(event.date.slice(5, 7)) - 1;

      if (!groups[month]) {
        groups[month] = [];
      }

      groups[month].push(event);
    });

    /*
     * Keep the existing July holiday behaviour.
     */
    if (hasClosed) {
      if (!groups[6]) {
        groups[6] = [];
      }

      groups[6].push({
        __closed: true
      });
    }

    const months = Object.keys(groups)
      .map(Number)
      .sort((a, b) => a - b);

    if (!months.length) {
      root.innerHTML = `
        <div class="calendar-empty">
          ${tr(
            "பூஜை நாட்கள் எதுவும் இல்லை.",
            "No pooja dates are available."
          )}
        </div>
      `;

      return;
    }

    root.innerHTML = months
      .map((month) => {

        const eventsHtml = groups[month]
          .map((event) => {
            return event.__closed
              ? closedHtml()
              : eventHtml(event);
          })
          .join("");

        return `
          <section class="pooja-month">

            <header class="pooja-month-header">

              <div>

                <span class="month-number">
                  ${String(month + 1).padStart(2, "0")}
                </span>

                <h3>
                  ${monthName(month)}
                </h3>

              </div>

              <span class="month-year">
                2026
              </span>

            </header>


            <div class="pooja-month-events">
              ${eventsHtml}
            </div>

          </section>
        `;
      })
      .join("");

    markNext();
    loadPhotos();
  }

  function markNext() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    let next = null;

    root
      .querySelectorAll("[data-date]")
      .forEach((element) => {

        const date =
          new Date(`${element.dataset.date}T00:00:00`);

        if (date < today) {
          element.classList.add("is-past");
          return;
        }

        if (
          !next ||
          date <
            new Date(`${next.dataset.date}T00:00:00`)
        ) {
          next = element;
        }
      });

    if (next) {
      next.classList.add("is-next");
      next.id = "next-pooja";
    }
  }

  function loadPhotos() {
    root
      .querySelectorAll("[data-photos]")
      .forEach((box) => {

        HM.photosFor(box.dataset.photos)
          .then((photos) => {

            box.innerHTML = photos
              .map((photo) => `
                <img
                  src="${photo.dataUrl}"
                  alt=""
                >
              `)
              .join("");

          })
          .catch(() => {});
      });
  }

  function toast(text, actionsHtml) {
    let element =
      document.getElementById("hm-toast");

    if (!element) {
      element = document.createElement("div");

      element.id = "hm-toast";
      element.className = "hm-toast";

      document.body.appendChild(element);
    }

    element.textContent = text;

    if (actionsHtml) {
      const extra =
        document.createElement("span");

      extra.innerHTML = actionsHtml;
      element.append(extra);
    }
  }

  function report(result, id, kind) {
    if (result.ok && kind === "mail") {

      const more =
        result.batches > 1
          ? ` ${result.batch}/${result.batches}`
          : "";

      const skipped =
        result.missing
          ? ` ${tr(
              "அஞ்சல் இல்லாத தொடர்பு",
              "contacts without email"
            )}: ${result.missing}.`
          : "";

      toast(
        `${tr(
          "கோவில் அஞ்சல் திறக்கப்பட்டது",
          "Temple mail opened"
        )}${more}.${skipped}`,

        result.batch < result.batches
          ? `
            <button
              type="button"
              data-send="mail"
              data-id="${id}"
              data-batch="${result.batch}"
            >
              ${tr(
                "அடுத்த தொகுதி",
                "Next batch"
              )}
            </button>
          `
          : ""
      );

      return;
    }

    if (result.ok) return;

    const reasons = {
      "no-mail": tr(
        "முதலில் அஞ்சல் முகவரியுடன் தொடர்புகளைச் சேருங்கள்.",
        "Add contacts with an email address first."
      ),

      "no-group": tr(
        "நிர்வாகப் பக்கத்தில் குழு அஞ்சல் முகவரியைச் சேருங்கள்.",
        "Add the group email on the committee page."
      ),

      missing: tr(
        "இந்தப் பூஜையைக் காணவில்லை.",
        "This pooja could not be found."
      )
    };

    toast(
      reasons[result.reason] ||
      reasons.missing
    );
  }

  /*
   * Detail accordion
   */
  root.addEventListener("click", (event) => {

    const button =
      event.target.closest("[data-detail]");

    if (!button) return;

    const row =
      button.closest(".pooja-event");

    if (!row) return;

    const open =
      row.classList.toggle("is-open");

    button.setAttribute(
      "aria-expanded",
      String(open)
    );

    button.innerHTML = open
      ? `
        ${tr("மறை", "Hide")}
        <span aria-hidden="true">↑</span>
      `
      : `
        ${tr("விவரம் பார்க்க", "View details")}
        <span aria-hidden="true">→</span>
      `;
  });

  /*
   * Save prasadam
   */
  root.addEventListener("change", (event) => {

    const select =
      event.target.closest("[data-prasadam]");

    if (!select) return;

    HM.savePooja(
      select.dataset.prasadam,
      {
        prasadam: select.value
      }
    );
  });

  /*
   * Mail / group / WhatsApp
   */
  document.addEventListener("click", (event) => {

    const button =
      event.target.closest("[data-send]");

    if (!button) return;

    if (
      !root.contains(button) &&
      !button.closest("#hm-toast")
    ) {
      return;
    }

    const id = button.dataset.id;
    const batch =
      Number(button.dataset.batch || 0);

    if (button.dataset.send === "mail") {
      report(
        HM.openMail(id, batch),
        id,
        "mail"
      );
    }

    if (button.dataset.send === "group") {
      report(
        HM.openGroup(id),
        id,
        "group"
      );
    }

    if (button.dataset.send === "whatsapp") {
      HM.openWhatsApp(id);
    }
  });

  /*
   * Re-render when language/live data changes.
   */
  document.addEventListener("hm-lang", render);
  document.addEventListener("hm-live", render);

  render();
})();