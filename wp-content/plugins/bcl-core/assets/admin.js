/* Media picker for "Ảnh hoạt động" / "Logo đối tác" on the home page. */
document.addEventListener('click', (e) => {
	const wrap = e.target.closest('.bcl-media');
	if (!wrap) return;
	const input = document.getElementById(wrap.dataset.input);
	const list = wrap.querySelector('.bcl-media__list');

	if (e.target.closest('.bcl-media__clear')) {
		input.value = '';
		list.replaceChildren();
		return;
	}
	if (!e.target.closest('.bcl-media__pick')) return;

	const frame = wp.media({ multiple: 'add', library: { type: 'image' } });
	frame.on('open', () => {
		const sel = frame.state().get('selection');
		input.value.split(',').filter(Boolean).forEach((id) => sel.add(wp.media.attachment(id)));
	});
	frame.on('select', () => {
		const items = frame.state().get('selection').toJSON();
		input.value = items.map((a) => a.id).join(',');
		list.replaceChildren(...items.map((a) => {
			const img = document.createElement('img');
			img.src = (a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail : a).url;
			img.alt = '';
			img.style.cssText = 'width:72px;height:72px;object-fit:cover;border-radius:4px';
			return img;
		}));
	});
	frame.open();
});
