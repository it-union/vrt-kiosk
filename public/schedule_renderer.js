(function () {
  if (window.ScheduleRenderer) return;

  const PRESETS = {
    content: {
      padding: '8px',
      tableFontSize: '14px',
      tableBorderVisible: true,
      tableBorderColor: '',
      dayWidth: '16%',
      dayPadding: '7px',
      dayFontSize: '14px',
      cellPadding: '7px',
      slotGap: '6px',
      slotMinHeight: '24px',
      badgePadding: '3px 7px',
      badgeBorderRadius: '999px',
      badgeFontSize: '12px',
      badgeStrikeMode: 'none',
      busyBadgeBg: '',
      busyBadgeText: '',
      freeBadgeBg: '',
      freeBadgeText: '',
      emptyFontSize: '12px',
    },
    template: {
      padding: '8px',
      tableFontSize: '14px',
      tableBorderVisible: true,
      tableBorderColor: '',
      dayWidth: '16%',
      dayPadding: '7px',
      dayFontSize: '14px',
      cellPadding: '7px',
      slotGap: '6px',
      slotMinHeight: '24px',
      badgePadding: '3px 7px',
      badgeBorderRadius: '999px',
      badgeFontSize: '12px',
      badgeStrikeMode: 'none',
      busyBadgeBg: '',
      busyBadgeText: '',
      freeBadgeBg: '',
      freeBadgeText: '',
      emptyFontSize: '12px',
    },
    preview: {
      padding: '20px',
      tableFontSize: '15px',
      tableBorderVisible: true,
      tableBorderColor: '',
      dayWidth: '16%',
      dayPadding: '8px',
      dayFontSize: '26px',
      cellPadding: '18px',
      slotGap: '7px',
      slotMinHeight: '30px',
      badgePadding: '10px 18px',
      badgeBorderRadius: '5px',
      badgeFontSize: '26px',
      badgeStrikeMode: 'busy',
      busyBadgeBg: '',
      busyBadgeText: '',
      freeBadgeBg: '',
      freeBadgeText: '',
      emptyFontSize: '13px',
    },
    kiosk: {
      padding: '20px',
      tableFontSize: '15px',
      tableBorderVisible: true,
      tableBorderColor: '',
      dayWidth: '16%',
      dayPadding: '8px',
      dayFontSize: '26px',
      cellPadding: '18px',
      slotGap: '7px',
      slotMinHeight: '30px',
      badgePadding: '10px 18px',
      badgeBorderRadius: '5px',
      badgeFontSize: '26px',
      badgeStrikeMode: 'busy',
      busyBadgeBg: '',
      busyBadgeText: '',
      freeBadgeBg: '',
      freeBadgeText: '',
      emptyFontSize: '13px',
    },
  };

  const FALLBACK_COLORS = {
    text: '#0f172a',
    header_bg: '#dbeafe',
    header_text: '#1e3a8a',
    grid_line: '#bfdbfe',
    busy_bg: '#fee2e2',
    busy_text: '#991b1b',
    free_bg: '#dcfce7',
    free_text: '#166534',
  };

  function extractRows(payload) {
    if (!payload || typeof payload !== 'object') return [];
    const days = Array.isArray(payload.days) ? payload.days : [];
    return days
      .map((day) => {
        if (typeof day === 'string') {
          return { label: day, slots: [] };
        }
        if (!day || typeof day !== 'object') {
          return null;
        }
        return {
          label: String(day.day || day.label || day.date || ''),
          slots: Array.isArray(day.slots)
            ? day.slots.filter((slot) => slot && typeof slot === 'object')
            : [],
        };
      })
      .filter((row) => row && String(row.label || '').trim() !== '');
  }

  function formatRuDateLabel(value) {
    const raw = String(value || '').trim();
    const m = raw.match(/^(\d{4})-(\d{2})-(\d{2})(?:[T\s].*)?$/);
    if (!m) return raw;
    const y = Number(m[1]);
    const mm = Number(m[2]);
    const d = Number(m[3]);
    if (!Number.isFinite(y) || !Number.isFinite(mm) || !Number.isFinite(d))
      return raw;
    const dt = new Date(y, mm - 1, d);
    if (Number.isNaN(dt.getTime())) return raw;
    return dt.toLocaleDateString('ru-RU');
  }

  function limitRowsByDepth(rows, depthDays) {
    const depth = Math.max(1, Math.min(31, Number(depthDays || 7)));
    return rows.slice(0, depth);
  }

  function render(params) {
    const input = params && typeof params === 'object' ? params : {};
    const schedule =
      input.schedule && typeof input.schedule === 'object'
        ? input.schedule
        : {};
    const theme =
      input.theme && typeof input.theme === 'object' ? input.theme : {};
    const colors =
      theme.colors && typeof theme.colors === 'object' ? theme.colors : {};
    const mode = String(input.mode || 'content');
    const scale = Number(input.scale || 1);
    const p = PRESETS[mode] || PRESETS.content;
    const borderColor = String(
      p.tableBorderColor || colors.grid_line || FALLBACK_COLORS.grid_line,
    );
    const borderStyle =
      p.tableBorderVisible === false ? 'none' : '1px solid ' + borderColor;
    const strikeMode = ['none', 'busy', 'free', 'all'].includes(
      String(p.badgeStrikeMode || 'none'),
    )
      ? String(p.badgeStrikeMode || 'none')
      : 'none';
    const showBusyRaw = schedule.show_busy;
    const showBusy = !(showBusyRaw === false || String(showBusyRaw) === '0');

    const safeScale = Number.isFinite(scale) && scale > 0 ? scale : 1;
    const scalePx = (value) => {
      const num = parseFloat(value);
      if (Number.isNaN(num)) return value;
      const unit = String(value).replace(/[\d.]+/, '');
      return (num * safeScale) + unit;
    };

    const wrap = document.createElement('div');
    wrap.style.width = '100%';
    wrap.style.height = '100%';
    wrap.style.boxSizing = 'border-box';
    wrap.style.padding = scalePx(p.padding);
    wrap.style.overflow = 'auto';
    wrap.style.color = String(colors.text || FALLBACK_COLORS.text);

    const rows = limitRowsByDepth(
      extractRows(schedule.cached_payload),
      Number(schedule.days || 7),
    );
    if (rows.length <= 0) {
      const empty = document.createElement('div');
      empty.style.fontSize = scalePx(p.emptyFontSize);
      empty.style.opacity = '0.8';
      empty.textContent = 'Нет кэшированных данных расписания';
      wrap.appendChild(empty);
      return wrap;
    }

    const table = document.createElement('table');
    table.style.width = '100%';
    table.style.borderCollapse = 'collapse';
    table.style.tableLayout = 'fixed';
    table.style.fontSize = scalePx(p.tableFontSize);

    const tbody = document.createElement('tbody');
    for (const row of rows) {
      const tr = document.createElement('tr');

      const th = document.createElement('th');
      th.textContent = formatRuDateLabel(String(row.label || ''));
      th.style.border = borderStyle;
      th.style.background = String(
        colors.header_bg || FALLBACK_COLORS.header_bg,
      );
      th.style.color = String(
        colors.header_text || FALLBACK_COLORS.header_text,
      );
      th.style.padding = scalePx(p.dayPadding);
      th.style.textAlign = 'center';
      th.style.width = p.dayWidth;
      th.style.fontSize = scalePx(p.dayFontSize);
      th.style.lineHeight = '1.2';
      tr.appendChild(th);

      const td = document.createElement('td');
      td.style.border = borderStyle;
      td.style.padding = scalePx(p.cellPadding);
      const slotsWrap = document.createElement('div');
      slotsWrap.style.display = 'flex';
      slotsWrap.style.flexWrap = 'wrap';
      slotsWrap.style.gap = scalePx(p.slotGap);
      slotsWrap.style.minHeight = scalePx(p.slotMinHeight);

      for (const slot of row.slots) {
        const badge = document.createElement('span');
        const statusRaw = String(slot.status || slot.state || '').toLowerCase();
        const isBusy =
          slot.busy === true ||
          statusRaw === 'busy' ||
          statusRaw === 'occupied';
        if (!showBusy && isBusy) {
          continue;
        }
        const from = String(slot.from || '').trim();
        const to = String(slot.to || '').trim();
        const label = String(
          slot.time || slot.label || (from && to ? from + '-' + to : 'Слот'),
        );
        badge.textContent = label;
        badge.style.display = 'inline-flex';
        badge.style.alignItems = 'center';
        badge.style.padding = scalePx(p.badgePadding);
        badge.style.borderRadius = scalePx(p.badgeBorderRadius);
        badge.style.border = borderStyle;
        badge.style.fontSize = scalePx(p.badgeFontSize);
        const busyBg = String(
          p.busyBadgeBg || colors.busy_bg || FALLBACK_COLORS.busy_bg,
        );
        const busyText = String(
          p.busyBadgeText || colors.busy_text || FALLBACK_COLORS.busy_text,
        );
        const freeBg = String(
          p.freeBadgeBg || colors.free_bg || FALLBACK_COLORS.free_bg,
        );
        const freeText = String(
          p.freeBadgeText || colors.free_text || FALLBACK_COLORS.free_text,
        );
        badge.style.background = isBusy ? busyBg : freeBg;
        badge.style.color = isBusy ? busyText : freeText;
        const strike =
          strikeMode === 'all' ||
          (strikeMode === 'busy' && isBusy) ||
          (strikeMode === 'free' && !isBusy);
        badge.style.textDecoration = strike ? 'line-through' : 'none';
        slotsWrap.appendChild(badge);
      }

      if (slotsWrap.childElementCount === 0) {
        const emptySlot = document.createElement('span');
        emptySlot.textContent = 'Нет окон';
        emptySlot.style.opacity = '0.75';
        emptySlot.style.fontSize = scalePx(p.badgeFontSize);
        slotsWrap.appendChild(emptySlot);
      }

      td.appendChild(slotsWrap);
      tr.appendChild(td);
      tbody.appendChild(tr);
    }

    table.appendChild(tbody);
    wrap.appendChild(table);
    return wrap;
  }

  window.ScheduleRenderer = {
    extractRows,
    render,
  };
})();
