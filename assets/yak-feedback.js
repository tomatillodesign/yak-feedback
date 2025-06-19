document.addEventListener('DOMContentLoaded', () => {
	console.log('[Yak Feedback] DOM fully loaded.');

	// ✅ Sanity check for localized PHP vars
	if (!window.YakFeedback || !YakFeedback.post_id) {
		console.warn('[Yak Feedback] Missing YakFeedback data. Aborting.');
		return;
	}

	// ✅ Simple flow structure — expandable later
	const feedbackFlow = {
		q1: {
			id: 'q1',
			text: 'How are you using this prayer?',
			options: [
				{ label: 'Personal devotion', next: 'q2a' },
				{ label: 'Worship planning', next: 'q2b' },
				{ label: 'Special gathering', next: 'q2c' },
				{ label: 'Something else', next: 'q2d' },
			]
		},
		q2a: { id: 'q2a', text: 'Have you shared this with anyone?', options: [] },
		q2b: { id: 'q2b', text: 'What kind of service are you planning?', options: [] },
		q2c: { id: 'q2c', text: 'What’s the occasion?', options: [] },
		q2d: { id: 'q2d', text: 'Tell us a bit more?', options: [] },
	};

	let currentStep = 'q1';
	const responses = [];

	// ✅ Feedback button must exist in DOM
	const feedbackBtn = document.querySelector('.yak-feedback-button');
	if (!feedbackBtn) {
		console.warn('[Yak Feedback] Trigger button not found.');
		return;
	}

	// ✅ Create panel if missing
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

	const questionEl = panel.querySelector('.yak-feedback-question');
	const optionsEl = panel.querySelector('.yak-feedback-options');
	const closeBtn = panel.querySelector('.yak-feedback-close');

	if (!questionEl || !optionsEl || !closeBtn) {
		console.error('[Yak Feedback] Feedback panel missing internal elements.');
		return;
	}

	// ✅ Load any previous saved session responses
	let saved = sessionStorage.getItem('yakFeedback');
	if (saved) {
		try {
			const parsed = JSON.parse(saved);
			if (Array.isArray(parsed)) {
				console.log('[Yak Feedback] Restored saved session responses:', parsed);
				responses.push(...parsed);
			}
		} catch (e) {
			console.warn('[Yak Feedback] Could not parse saved session.');
		}
	}

	// ✅ Save response to sessionStorage
	function saveResponse(question, answer) {
		responses.push({ question, answer });
		sessionStorage.setItem('yakFeedback', JSON.stringify(responses));
		console.log('[Yak Feedback] Response saved:', { question, answer });
	}

	// ✅ Close handler
	closeBtn.addEventListener('click', () => {
		panel.classList.remove('open');
		console.log('[Yak Feedback] Panel closed manually.');
		submitIfNeeded(); // Try background submit
	});

	// ✅ Trigger panel open
	feedbackBtn.addEventListener('click', () => {
		currentStep = 'q1';
		if (responses.length === 0) {
			renderStep(currentStep);
		}
		panel.classList.add('open');
	});

	// ✅ Step renderer
	function renderStep(stepId) {
		const step = feedbackFlow[stepId];
		if (!step) return;

		console.log('[Yak Feedback] Showing step:', stepId);
		questionEl.textContent = step.text;
		optionsEl.innerHTML = '';

		if (step.options.length === 0) {
			const input = document.createElement('textarea');
			input.rows = 3;
			input.placeholder = 'Your response...';
			input.className = 'yak-feedback-textarea';

			const submitBtn = document.createElement('button');
			submitBtn.className = 'yak-feedback-submit';
			submitBtn.textContent = 'Submit';

			submitBtn.addEventListener('click', () => {
				const val = input.value.trim();
				if (!val) return;
				saveResponse(step.text, val);
				submitFeedback();
			});

			optionsEl.appendChild(input);
			optionsEl.appendChild(submitBtn);
			input.focus();
			return;
		}

		step.options.forEach(opt => {
			const btn = document.createElement('button');
			btn.className = 'yak-feedback-option';
			btn.textContent = opt.label;
			btn.addEventListener('click', () => {
				saveResponse(step.text, opt.label);
				if (opt.next) {
					renderStep(opt.next);
				} else {
					submitFeedback();
				}
			});
			optionsEl.appendChild(btn);
		});
	}

	// ✅ Regular submission via fetch
	function submitFeedback() {
		panel.classList.remove('open');

		console.log('[Yak Feedback] Submitting via fetch():', responses);
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
				alert('✅ Thanks for your feedback!');
				responses.length = 0;           // Clear in-memory array
				currentStep = 'q1';             // Reset step
			} else {
				alert('❌ Feedback failed to send.');
			}
		})
		.catch(err => {
			console.error('[Yak Feedback] AJAX error:', err);
			alert('⚠️ Something went wrong.');
		});
	}

	// ✅ Background submit via sendBeacon before unload
	function submitIfNeeded() {
		const saved = sessionStorage.getItem('yakFeedback');
		if (!saved) return;

		try {
			const parsed = JSON.parse(saved);
			if (parsed.length > 0) {
				console.log('[Yak Feedback] Submitting background feedback via sendBeacon');
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
		} catch (e) {
			console.warn('[Yak Feedback] Failed to parse stored responses on unload.');
		}
	}

	// ✅ Attach unload + pagehide triggers
	window.addEventListener('beforeunload', submitIfNeeded);
	window.addEventListener('pagehide', submitIfNeeded); // mobile/safari
});
