document.addEventListener('DOMContentLoaded', () => {
	console.log('[Yak Feedback] DOM fully loaded.');

	// ✅ Sanity check for localized PHP vars
	if (!window.YakFeedback || !YakFeedback.post_id || !YakFeedback.flow_url) {
		console.warn('[Yak Feedback] Missing YakFeedback config. Aborting.');
		return;
	}

	// ✅ Fetch external flow JSON
	fetch(YakFeedback.flow_url)
		.then(res => res.json())
		.then(flow => {
			console.log('[Yak Feedback] Flow loaded:', flow);
			startFeedbackFlow(flow);
		})
		.catch(err => {
			console.error('[Yak Feedback] Failed to load flow:', err);
		});
});

function startFeedbackFlow(feedbackFlow) {
	let currentStep = 'start';
	const responses = [];

	const feedbackBtn = document.querySelector('.yak-feedback-button');
	if (!feedbackBtn) return;

	let panel = document.querySelector('.yak-feedback-panel');
	if (!panel) {
		panel = document.createElement('div');
		panel.className = 'yak-feedback-panel';
		panel.innerHTML = `
			<div class="yak-feedback-inner">
				<div class="yak-feedback-question"></div>
				<div class="yak-feedback-options"></div>
				<button class="yak-feedback-close" aria-label="Close">&times;</button>
			</div>
		`;
		document.body.appendChild(panel);
	}

	const inner = panel.querySelector('.yak-feedback-inner');
	const questionEl = panel.querySelector('.yak-feedback-question');
	const optionsEl = panel.querySelector('.yak-feedback-options');
	const closeBtn = panel.querySelector('.yak-feedback-close');
	if (!questionEl || !optionsEl || !closeBtn) return;

	// Restore from session
	let saved = sessionStorage.getItem('yakFeedback');
	if (saved) {
		try {
			const parsed = JSON.parse(saved);
			if (Array.isArray(parsed)) responses.push(...parsed);
		} catch (e) {}
	}

	function saveResponse(question, answer) {
		responses.push({ question, answer });
		sessionStorage.setItem('yakFeedback', JSON.stringify(responses));
	}

	// Prevent clicks inside the inner panel from bubbling up and closing the modal
	inner.addEventListener('click', e => e.stopPropagation());

	closeBtn.addEventListener('click', () => {
		panel.classList.remove('open');
		submitIfNeeded();
	});

	// Close when clicking outside the inner panel
	panel.addEventListener('click', (e) => {
		if (!e.target.closest('.yak-feedback-inner')) {
			panel.classList.remove('open');
			submitIfNeeded(); // submit partial feedback before close if needed
		}
	});


	feedbackBtn.addEventListener('click', () => {
		currentStep = 'start';
		responses.length = 0;
		sessionStorage.removeItem('yakFeedback');
		renderStep(currentStep);
		panel.classList.add('open');
	});

	function renderStep(stepId) {
		const step = feedbackFlow[stepId];
		if (!step) return;
		currentStep = stepId;
		questionEl.textContent = step.question;
		optionsEl.innerHTML = '';

		if (step.type === 'text') {
			const input = document.createElement('textarea');
			input.rows = 3;
			input.className = 'yak-feedback-textarea';
			input.placeholder = 'Type your response…';

			const nextBtn = document.createElement('button');
			nextBtn.className = 'yak-feedback-submit';
			nextBtn.textContent = 'Next';

			nextBtn.addEventListener('click', () => {
				const val = input.value.trim();
				saveResponse(step.question, val); // allow blank
				renderStep(step.next);
			});


			optionsEl.appendChild(input);
			optionsEl.appendChild(nextBtn);
			input.focus();
			return;
		}

		if (step.type === 'end') {
			const msg = document.createElement('p');
			msg.textContent = '';
			optionsEl.appendChild(msg);
			setTimeout(submitFeedback, 2000);
			return;
		}

		step.options?.forEach(opt => {
			const btn = document.createElement('button');
			btn.className = 'yak-feedback-option';
			btn.textContent = opt.label;
			btn.addEventListener('click', () => {
				saveResponse(step.question, opt.label);
				renderStep(opt.next);
			});
			optionsEl.appendChild(btn);
		});
	}

	function submitFeedback() {
		panel.classList.remove('open');
		console.log('[Yak Feedback] Submitting responses:', responses);

		fetch(YakFeedback.ajax_url, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams({
				action: 'yak_feedback_submit',
				nonce: YakFeedback.nonce,
				post_id: YakFeedback.post_id,
				responses: JSON.stringify(responses)
			})
		})
		.then(res => res.json())
		.then(data => {
			console.log('[Yak Feedback] Submission response:', data);
			if (data.success) {
				sessionStorage.removeItem('yakFeedback');
				console.log('[Yak Feedback] ✅ Submission successful.');
				responses.length = 0;
				currentStep = 'q1';

				// Optional: toast-style message
				const msg = document.createElement('div');
				msg.className = 'yak-feedback-toast';
				msg.textContent = 'Thanks for your feedback!';
				document.body.appendChild(msg);

				setTimeout(() => {
					msg.classList.add('visible');
					setTimeout(() => {
						msg.classList.remove('visible');
						setTimeout(() => msg.remove(), 500);
					}, 3000);
				}, 10);
}			 else {
				alert('❌ Feedback failed to send.');
			}
		})
		.catch(err => {
			console.error('[Yak Feedback] AJAX error:', err);
			alert('⚠️ Something went wrong.');
		});
	}

	function submitIfNeeded() {
		const saved = sessionStorage.getItem('yakFeedback');
		if (!saved) return;

		try {
			const parsed = JSON.parse(saved);
			if (parsed.length > 0) {
				navigator.sendBeacon(
					YakFeedback.ajax_url,
					new URLSearchParams({
						action: 'yak_feedback_submit',
						nonce: YakFeedback.nonce,
						post_id: YakFeedback.post_id,
						responses: JSON.stringify(parsed)
					})
				);
				sessionStorage.removeItem('yakFeedback');
			}
		} catch (e) {}
	}

	window.addEventListener('beforeunload', submitIfNeeded);
	window.addEventListener('pagehide', submitIfNeeded);
}
