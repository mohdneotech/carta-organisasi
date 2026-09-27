/* Carta Organisasi Masjid — admin editor */
(function ($) {
	'use strict';
	var data = window.PC_DATA || { tajuk: '', nota: '', warna_utama: '#3f6b2a', warna_aksen: '#7cbf3f', tiers: [] };
	var $tiers = $('#pc-tiers');
	var dirty = false;

	function esc(s) {
		return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}

	function memberHTML(m) {
		m = m || {};
		var img = m.thumb || m.img_url || '';
		return '' +
			'<li class="pc-m" data-img-id="' + esc(m.img_id || 0) + '" data-img-url="' + esc(m.img_id ? '' : (m.img_url || '')) + '">' +
			'<span class="pc-handle dashicons dashicons-move" title="Seret untuk susun"></span>' +
			'<button type="button" class="pc-pic' + (img ? ' has' : '') + '" title="Pilih / tukar gambar">' +
				(img ? '<img src="' + esc(img) + '" alt="">' : '<span class="dashicons dashicons-format-image"></span><small>Gambar</small>') +
			'</button>' +
			'<div class="pc-fields">' +
				'<label>Jawatan<input type="text" class="f-jawatan" value="' + esc(m.jawatan) + '" placeholder="cth. Imam 2"></label>' +
				'<label>Nama<input type="text" class="f-nama" value="' + esc(m.nama) + '" placeholder="kosong = [Nama]"></label>' +
				'<label>Keterangan <span class="pc-opt">(pilihan)</span><input type="text" class="f-ket" value="' + esc(m.keterangan) + '" placeholder="cth. Ketua Kampung"></label>' +
			'</div>' +
			'<div class="pc-mbtns">' +
				'<button type="button" class="button-link pc-rmpic"' + (img ? '' : ' hidden') + '>Buang gambar</button>' +
				'<button type="button" class="button-link pc-del-m" title="Padam jawatan"><span class="dashicons dashicons-trash"></span></button>' +
			'</div>' +
			'</li>';
	}

	function tierHTML(t) {
		t = t || { label: '', sorot: false, members: [] };
		var ms = (t.members || []).map(memberHTML).join('');
		return '' +
			'<div class="pc-t">' +
			'<div class="pc-thead">' +
				'<span class="pc-thandle dashicons dashicons-move" title="Seret untuk susun peringkat"></span>' +
				'<strong class="pc-tno"></strong>' +
				'<input type="text" class="f-label" value="' + esc(t.label) + '" placeholder="Nama peringkat (untuk rujukan AJK sahaja)">' +
				'<label class="pc-sorot-lbl"><input type="checkbox" class="f-sorot"' + (t.sorot ? ' checked' : '') + '> Serlahkan (warna hijau muda)</label>' +
				'<span class="pc-tbtns">' +
					'<button type="button" class="button button-small pc-up" title="Naik">▲</button>' +
					'<button type="button" class="button button-small pc-down" title="Turun">▼</button>' +
					'<button type="button" class="button button-small pc-del-t" title="Padam peringkat"><span class="dashicons dashicons-trash"></span></button>' +
				'</span>' +
			'</div>' +
			'<ul class="pc-ms">' + ms + '</ul>' +
			'<button type="button" class="button pc-add-m"><span class="dashicons dashicons-plus"></span> Tambah jawatan</button>' +
			'</div>';
	}

	function collect() {
		var out = { tajuk: $('#pc-tajuk').val(), nota: $('#pc-nota').val(), warna_utama: $('#pc-warna-utama').val(), warna_aksen: $('#pc-warna-aksen').val(), tiers: [] };
		$tiers.children('.pc-t').each(function () {
			var $t = $(this), t = { label: $t.find('.f-label').val(), sorot: $t.find('.f-sorot').is(':checked'), members: [] };
			$t.find('.pc-m').each(function () {
				var $m = $(this);
				t.members.push({
					jawatan: $m.find('.f-jawatan').val(),
					nama: $m.find('.f-nama').val(),
					keterangan: $m.find('.f-ket').val(),
					img_id: parseInt($m.attr('data-img-id'), 10) || 0,
					img_url: $m.attr('data-img-url') || '',
					thumb: $m.find('.pc-pic img').attr('src') || ''
				});
			});
			out.tiers.push(t);
		});
		return out;
	}

	function preview() {
		var d = collect(), h = '<div class="pc-carta" style="--pc-green:' + esc(d.warna_utama) + ';--pc-leaf:' + esc(d.warna_aksen) + ';">';
		if (d.tajuk) h += '<p class="pc-tajuk">' + esc(d.tajuk) + '</p>';
		var first = true;
		d.tiers.forEach(function (t) {
			if (!t.members.length) return;
			var photos = t.members.some(function (m) { return m.thumb; });
			if (!first) h += '<div class="pc-line"></div>';
			first = false;
			h += '<div class="pc-tier' + (t.sorot ? ' pc-sorot' : '') + '">';
			t.members.forEach(function (m) {
				h += '<div class="pc-node">';
				if (photos) h += m.thumb ? '<img class="pc-foto" src="' + esc(m.thumb) + '" alt="">' : '<span class="pc-foto pc-foto-kosong"><span class="dashicons dashicons-admin-users"></span></span>';
				h += '<div class="pc-jawatan">' + esc(m.jawatan) + '</div>';
				if (m.keterangan) h += '<div class="pc-ket">' + esc(m.keterangan) + '</div>';
				h += '<div class="pc-nama">' + (m.nama ? esc(m.nama) : '<em class="pc-placeholder">[Nama]</em>') + '</div></div>';
			});
			h += '</div>';
		});
		if (d.nota) h += '<p class="pc-nota">' + esc(d.nota).replace(/\n/g, '<br>') + '</p>';
		$('#pc-preview').html(h + '</div>');
	}

	function refresh(markDirty) {
		$tiers.children('.pc-t').each(function (i) { $(this).find('.pc-tno').text('Peringkat ' + (i + 1)); });
		if (markDirty) { dirty = true; $('.pc-dirty').prop('hidden', false); }
		preview();
	}

	function sortables() {
		$tiers.sortable({ handle: '.pc-thandle', axis: 'y', placeholder: 'pc-t-ph', forcePlaceholderSize: true, update: function () { refresh(true); } });
		$tiers.find('.pc-ms').sortable({ handle: '.pc-handle', connectWith: '.pc-ms', placeholder: 'pc-m-ph', forcePlaceholderSize: true, update: function () { refresh(true); } });
	}

	// ---- build
	(data.tiers || []).forEach(function (t) { $tiers.append(tierHTML(t)); });
	if (!data.tiers || !data.tiers.length) $tiers.append(tierHTML());
	sortables();
	refresh(false);

	// ---- events
	$('#pc-add-tier').on('click', function () {
		var $t = $(tierHTML({ members: [{}] })).appendTo($tiers);
		sortables(); refresh(true);
		$t.find('.f-jawatan').first().trigger('focus');
	});
	$tiers.on('click', '.pc-add-m', function () {
		var $m = $(memberHTML({})).appendTo($(this).siblings('.pc-ms'));
		refresh(true); $m.find('.f-jawatan').trigger('focus');
	});
	$tiers.on('click', '.pc-del-m', function () {
		var $m = $(this).closest('.pc-m'), nm = $m.find('.f-jawatan').val() || 'jawatan ini';
		if (confirm('Padam "' + nm + '"?')) { $m.remove(); refresh(true); }
	});
	$tiers.on('click', '.pc-del-t', function () {
		var $t = $(this).closest('.pc-t');
		if (confirm('Padam seluruh peringkat ini beserta ' + $t.find('.pc-m').length + ' jawatan?')) { $t.remove(); refresh(true); }
	});
	$tiers.on('click', '.pc-up', function () { var $t = $(this).closest('.pc-t'); $t.prev('.pc-t').before($t); refresh(true); });
	$tiers.on('click', '.pc-down', function () { var $t = $(this).closest('.pc-t'); $t.next('.pc-t').after($t); refresh(true); });
	$('#pc-form').on('input change', 'input, textarea', function () { refresh(true); });

	// media picker
	var frame, $cur;
	$tiers.on('click', '.pc-pic', function () {
		$cur = $(this).closest('.pc-m');
		if (!frame) {
			frame = wp.media({ title: 'Pilih gambar', button: { text: 'Guna gambar ini' }, library: { type: 'image' }, multiple: false });
			frame.on('select', function () {
				var a = frame.state().get('selection').first().toJSON();
				var u = (a.sizes && (a.sizes.thumbnail || a.sizes.medium) || a).url;
				$cur.attr('data-img-id', a.id).attr('data-img-url', '');
				$cur.find('.pc-pic').addClass('has').html('<img src="' + esc(u) + '" alt="">');
				$cur.find('.pc-rmpic').prop('hidden', false);
				refresh(true);
			});
		}
		frame.open();
	});
	$tiers.on('click', '.pc-rmpic', function () {
		var $m = $(this).closest('.pc-m');
		$m.attr('data-img-id', 0).attr('data-img-url', '');
		$m.find('.pc-pic').removeClass('has').html('<span class="dashicons dashicons-format-image"></span><small>Gambar</small>');
		$(this).prop('hidden', true);
		refresh(true);
	});

	$('#pc-form').on('submit', function () {
		var d = collect();
		d.tiers.forEach(function (t) { t.members.forEach(function (m) { delete m.thumb; }); });
		$('#pc-json').val(JSON.stringify(d));
		dirty = false;
	});
	window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
})(jQuery);
