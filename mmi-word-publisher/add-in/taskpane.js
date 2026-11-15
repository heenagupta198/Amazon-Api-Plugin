/* global Office, Word */

(function () {
  "use strict";

  const STORAGE_KEY = "mmi_word_publisher_settings_v1";

  function $(id) {
    return document.getElementById(id);
  }

  function showStatus(message, isError) {
    const el = $("status");
    el.textContent = message;
    el.className = "show " + (isError ? "err" : "ok");
  }

  function loadSettings() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return;
      const s = JSON.parse(raw);
      if (s.siteUrl) $("siteUrl").value = s.siteUrl;
      if (s.wpUser) $("wpUser").value = s.wpUser;
      if (s.appPass) $("appPass").value = s.appPass;
    } catch (e) {
      /* ignore */
    }
  }

  function saveSettings() {
    const siteUrl = $("siteUrl").value.trim().replace(/\/$/, "");
    const wpUser = $("wpUser").value.trim();
    const appPass = $("appPass").value.replace(/\s+/g, "");
    if (!siteUrl || !wpUser || !appPass) {
      showStatus("Site URL, username aur Application Password zaroori hai.", true);
      return;
    }
    localStorage.setItem(
      STORAGE_KEY,
      JSON.stringify({ siteUrl, wpUser, appPass })
    );
    showStatus("Settings save ho gayi. Ab Send dabayein.", false);
  }

  function getSettings() {
    const siteUrl = $("siteUrl").value.trim().replace(/\/$/, "");
    const wpUser = $("wpUser").value.trim();
    const appPass = $("appPass").value.replace(/\s+/g, "");
    if (!siteUrl || !wpUser || !appPass) {
      throw new Error("Pehle settings save karein (Application Password).");
    }
    return { siteUrl, wpUser, appPass };
  }

  function authHeader(user, pass) {
    const token = btoa(unescape(encodeURIComponent(user + ":" + pass)));
    return "Basic " + token;
  }

  async function testConnection() {
    const { siteUrl, wpUser, appPass } = getSettings();
    showStatus("Testing…", false);
    const res = await fetch(siteUrl + "/wp-json/mmi-word-publisher/v1/ping", {
      method: "GET",
      headers: { Authorization: authHeader(wpUser, appPass) },
    });
    if (!res.ok) {
      const text = await res.text();
      throw new Error("Ping failed (" + res.status + "): " + text);
    }
    const data = await res.json();
    showStatus("Connected: " + (data.site || "OK"), false);
  }

  function normalizeWordHtml(html) {
    if (!html) return "";
    let out = html;
    out = out.replace(/<p[^>]*>\s*<b>\s*<span[^>]*>(.*?)<\/span>\s*<\/b>\s*<\/p>/gi, "<h3>$1</h3>");
    out = out.replace(/<p[^>]*>\s*<strong>(.*?)<\/strong>\s*<\/p>/gi, function (_m, inner) {
      if (inner.indexOf("<") === -1 && inner.length < 120) {
        return "<h3>" + inner + "</h3>";
      }
      return _m;
    });
    return out;
  }

  async function getTitleAndHtml() {
    return Word.run(async function (context) {
      const body = context.document.body;
      body.load("text");
      const titleProp = context.document.properties.title;
      titleProp.load("value");

      const paras = body.paragraphs;
      paras.load("items");
      await context.sync();

      let title = (titleProp.value || "").trim();
      if (!title && paras.items.length) {
        for (let i = 0; i < Math.min(paras.items.length, 5); i++) {
          paras.items[i].load("styleBuiltIn,text");
        }
        await context.sync();
        for (let i = 0; i < Math.min(paras.items.length, 5); i++) {
          const p = paras.items[i];
          const style = String(p.styleBuiltIn);
          if (style === "Heading1" || style === "heading1") {
            title = (p.text || "").trim();
            break;
          }
        }
      }
      if (!title && paras.items.length) {
        title = (paras.items[0].text || "").trim().slice(0, 120);
      }

      const htmlResult = body.getHtml();
      await context.sync();
      let contentHtml = htmlResult.value || "";
      contentHtml = normalizeWordHtml(contentHtml);

      if (title && paras.items.length) {
        const firstText = (paras.items[0].text || "").trim();
        if (firstText === title) {
          contentHtml = contentHtml.replace(/^[\s\S]*?<\/p>/i, "");
        }
      }

      return { title, contentHtml };
    });
  }

  async function sendDraft() {
    const { siteUrl, wpUser, appPass } = getSettings();
    showStatus("Word se read ho raha hai…", false);

    const { title, contentHtml } = await getTitleAndHtml();
    if (!title) {
      throw new Error("Title nahi mila. Heading 1 ya File → Info → Title set karein.");
    }

    showStatus("WordPress par draft save…", false);
    const res = await fetch(siteUrl + "/wp-json/mmi-word-publisher/v1/draft", {
      method: "POST",
      headers: {
        Authorization: authHeader(wpUser, appPass),
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ title, content: contentHtml }),
    });

    const payload = await res.json().catch(function () {
      return {};
    });

    if (!res.ok) {
      const msg = payload.message || payload.code || res.statusText;
      throw new Error(typeof msg === "string" ? msg : JSON.stringify(payload));
    }

    showStatus(
      "Draft save (#" + payload.id + "). WP mein images add karein, phir publish.",
      false
    );
  }

  Office.onReady(function () {
    loadSettings();
    $("btnSave").onclick = function () {
      try {
        saveSettings();
      } catch (e) {
        showStatus(e.message, true);
      }
    };
    $("btnTest").onclick = function () {
      testConnection().catch(function (e) {
        showStatus(e.message, true);
      });
    };
    $("btnSend").onclick = function () {
      sendDraft().catch(function (e) {
        showStatus(e.message, true);
      });
    };
  });
})();
