/* BCL theme: mobile nav, GA4 click events, RFQ form helpers. No dependencies. */
(() => {
	const body = document.body;
	const toggle = document.querySelector('.nav-toggle');

	// Mobile navigation
	const setNav = (open) => {
		body.classList.toggle('nav-open', open);
		toggle?.setAttribute('aria-expanded', String(open));
	};
	toggle?.addEventListener('click', () => {
		setNav(!body.classList.contains('nav-open'));
		if (body.classList.contains('nav-open')) document.querySelector('#site-nav a')?.focus();
	});
	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape' && body.classList.contains('nav-open')) {
			setNav(false);
			toggle.focus();
		}
	});
	matchMedia('(min-width: 1080px)').addEventListener('change', (e) => e.matches && setNav(false));

	// GA4 events (only when gtag is loaded, i.e. a Measurement ID is set in "Cài đặt BCL")
	const track = (name, params = {}) => {
		if (typeof window.gtag === 'function') window.gtag('event', name, params);
	};
	document.addEventListener('click', (e) => {
		const el = e.target.closest('[data-track]');
		if (el) track(el.dataset.track, { link_url: el.href || '', link_text: el.textContent.trim().slice(0, 60) });
	});

	// RFQ: success → lead event + focus; errors → focus summary
	const success = document.querySelector('[data-bcl-event]');
	if (success) {
		track(success.dataset.bclEvent, { form_ref: success.dataset.bclRef });
		success.focus();
	}
	document.querySelector('.rfq__alert')?.focus();

	// RFQ: check attachments before upload, prevent double submit
	document.querySelectorAll('form.rfq').forEach((form) => {
		const files = form.querySelector('input[type=file]');
		files?.addEventListener('change', () => {
			const list = [...files.files];
			const tooMany = list.length > Number(form.dataset.maxFiles);
			const tooBig = list.some((f) => f.size > Number(form.dataset.maxBytes));
			files.setCustomValidity(tooMany || tooBig ? form.dataset.msgFiles : '');
			files.reportValidity();
		});
		form.addEventListener('submit', () => {
			const btn = form.querySelector('[type=submit]');
			btn.disabled = true;
			btn.setAttribute('aria-busy', 'true');
		});
	});
})();
