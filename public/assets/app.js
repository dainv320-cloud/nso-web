document.addEventListener('DOMContentLoaded', () => {
    const backdrop = document.querySelector('[data-launch-popup-backdrop]');
    const popupStorageKey = 'launch-popup-dismissed';

    if (!backdrop) {
        return;
    }

    const isDismissed = window.localStorage.getItem(popupStorageKey) === '1';
    const deadlineRaw = backdrop.dataset.launchPopupDeadline || '';
    const deadline = deadlineRaw ? new Date(deadlineRaw) : null;
    const dayEl = backdrop.querySelector('[data-launch-days]');
    const hourEl = backdrop.querySelector('[data-launch-hours]');
    const minuteEl = backdrop.querySelector('[data-launch-minutes]');
    const secondEl = backdrop.querySelector('[data-launch-seconds]');
    const inlineEl = backdrop.querySelector('[data-launch-popup-inline]');
    let timerId = null;

    const lockScroll = () => {
        document.body.classList.add('auth-open');
    };

    const unlockScroll = () => {
        document.body.classList.remove('auth-open');
    };

    const closePopup = () => {
        backdrop.classList.remove('is-open');
        backdrop.setAttribute('aria-hidden', 'true');
        unlockScroll();

        if (timerId) {
            window.clearInterval(timerId);
            timerId = null;
        }
    };

    const formatNumber = (value) => String(Math.max(0, value)).padStart(2, '0');
    const renderCountdown = () => {
        if (!(deadline instanceof Date) || Number.isNaN(deadline.getTime())) {
            return;
        }

        const diff = Math.max(0, deadline.getTime() - Date.now());
        const totalSeconds = Math.floor(diff / 1000);
        const days = Math.floor(totalSeconds / 86400);
        const hours = Math.floor((totalSeconds % 86400) / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        if (dayEl) dayEl.textContent = formatNumber(days);
        if (hourEl) hourEl.textContent = formatNumber(hours);
        if (minuteEl) minuteEl.textContent = formatNumber(minutes);
        if (secondEl) secondEl.textContent = formatNumber(seconds);
        if (inlineEl) {
            inlineEl.textContent = `${days} ngày ${formatNumber(hours)}:${formatNumber(minutes)}:${formatNumber(seconds)}`;
        }
    };

    if (isDismissed) {
        return;
    }

    backdrop.classList.add('is-open');
    backdrop.setAttribute('aria-hidden', 'false');
    lockScroll();
    renderCountdown();
    timerId = window.setInterval(renderCountdown, 1000);

    backdrop.querySelectorAll('[data-launch-popup-close]').forEach((button) => {
        button.addEventListener('click', closePopup);
    });

    backdrop.querySelectorAll('a[href]').forEach((link) => {
        link.addEventListener('click', () => {
            window.localStorage.setItem(popupStorageKey, '1');
            closePopup();
        });
    });

    backdrop.addEventListener('click', (event) => {
        if (event.target === backdrop) {
            closePopup();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && backdrop.classList.contains('is-open')) {
            closePopup();
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const moneyInputs = Array.from(document.querySelectorAll('[data-money-input]'));

    if (moneyInputs.length === 0) {
        return;
    }

    const formatMoney = (value) => {
        const digits = String(value || '').replace(/\D/g, '');

        if (!digits) {
            return '';
        }

        return Number.parseInt(digits, 10).toLocaleString('vi-VN');
    };

    moneyInputs.forEach((input) => {
        const applyFormat = () => {
            input.value = formatMoney(input.value);
        };

        input.addEventListener('input', applyFormat);
        input.addEventListener('blur', applyFormat);
        applyFormat();
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const converter = document.querySelector('[data-payment-converter]');

    if (!converter) {
        return;
    }

    const amountInput = converter.querySelector('input[name="amount"]');
    const coinOutput = converter.querySelector('[data-payment-coin-output]');
    const rateText = converter.querySelector('[data-payment-rate-text]');
    const effectiveRate = Number.parseFloat(converter.dataset.paymentRate || '1') || 1;
    const baseRate = Number.parseFloat(converter.dataset.paymentBaseRate || '1') || 1;
    const campaignName = converter.dataset.paymentCampaignName || '';
    const campaignBonus = Number.parseFloat(converter.dataset.paymentCampaignBonus || '0') || 0;

    if (!amountInput || !coinOutput) {
        return;
    }

    const formatMoney = (value) => Number.parseInt(String(value || '0'), 10).toLocaleString('vi-VN');
    const parseDigits = (value) => Number.parseInt(String(value || '').replace(/\D/g, ''), 10) || 0;

    const updateCoinPreview = () => {
        const amount = parseDigits(amountInput.value);
        const coin = Math.floor(amount * effectiveRate);
        coinOutput.value = formatMoney(coin);

        if (!rateText) {
            return;
        }

        if (campaignName) {
            rateText.textContent = `Campaign ${campaignName}: ${baseRate} x (1 + ${campaignBonus}%) = ${effectiveRate} coin / 1 VND.`;
            return;
        }

        rateText.textContent = `Ty le hien tai: ${effectiveRate} coin / 1 VND.`;
    };

    amountInput.addEventListener('input', updateCoinPreview);
    amountInput.addEventListener('blur', updateCoinPreview);
    updateCoinPreview();
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-toggle-password]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = button.closest('.input-wrap')?.querySelector('input');

            if (!input) {
                return;
            }

            input.type = input.type === 'password' ? 'text' : 'password';
            button.setAttribute('aria-label', input.type === 'password' ? 'Hiện mật khẩu' : 'Ẩn mật khẩu');
        });
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-register-form]');

    if (!form) {
        return;
    }

    const password = form.querySelector('[data-register-password]');
    const confirmPassword = form.querySelector('[data-register-confirm-password]');
    const error = form.querySelector('[data-register-password-error]');

    if (!password || !confirmPassword || !error) {
        return;
    }

    const validatePasswords = () => {
        const shouldValidate = confirmPassword.value.length > 0;
        const isMatch = password.value === confirmPassword.value;

        confirmPassword.closest('.input-wrap')?.classList.toggle('has-error', shouldValidate && !isMatch);
        error.textContent = shouldValidate && !isMatch ? 'Mật khẩu nhập lại không trùng.' : '';
        confirmPassword.setCustomValidity(shouldValidate && !isMatch ? 'Mật khẩu nhập lại không trùng.' : '');

        return !shouldValidate || isMatch;
    };

    password.addEventListener('input', validatePasswords);
    confirmPassword.addEventListener('input', validatePasswords);
    form.addEventListener('submit', (event) => {
        if (!validatePasswords()) {
            event.preventDefault();
            confirmPassword.focus();
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-ns-toast]').forEach((toast) => {
        const close = () => {
            toast.classList.add('is-closing');
            setTimeout(() => toast.remove(), 180);
        };

        toast.querySelector('[data-ns-toast-close]')?.addEventListener('click', close);
        setTimeout(close, 3000);
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const menus = Array.from(document.querySelectorAll('[data-account-menu]'));

    if (menus.length === 0) {
        return;
    }

    const closeAll = () => {
        menus.forEach((menu) => {
            menu.classList.remove('is-open');
            menu.querySelector('[data-account-toggle]')?.setAttribute('aria-expanded', 'false');
        });
    };

    closeAll();

    menus.forEach((menu) => {
        const toggle = menu.querySelector('[data-account-toggle]');

        toggle?.addEventListener('click', (event) => {
            event.stopPropagation();
            const willOpen = !menu.classList.contains('is-open');
            closeAll();
            menu.classList.toggle('is-open', willOpen);
            toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
    });

    document.addEventListener('click', closeAll);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAll();
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const backdrop = document.querySelector('[data-payment-qr-backdrop]');

    if (!backdrop) {
        return;
    }

    const statusText = backdrop.querySelector('[data-payment-status-text]');
    const statusUrl = backdrop.dataset.paymentStatusUrl || '';
    let pollingTimer = null;
    let isResolved = false;

    const closeQr = () => {
        backdrop.classList.remove('is-open');
        backdrop.setAttribute('aria-hidden', 'true');

        if (pollingTimer) {
            window.clearTimeout(pollingTimer);
            pollingTimer = null;
        }
    };

    const showToast = (message, isError = false) => {
        let stack = document.querySelector('.ns-toast-stack');

        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'ns-toast-stack';
            stack.setAttribute('aria-live', 'polite');
            document.body.appendChild(stack);
        }

        const toast = document.createElement('div');
        toast.className = `ns-toast ${isError ? 'ns-toast-error' : 'ns-toast-success'}`;
        toast.innerHTML = `
            <span class="ns-toast-icon" aria-hidden="true">${isError ? '!' : '&#10003;'}</span>
            <strong>${message}</strong>
            <button type="button" class="ns-toast-close" aria-label="Đóng">&times;</button>
            <span class="ns-toast-progress" aria-hidden="true"></span>
        `;

        const close = () => {
            toast.classList.add('is-closing');
            window.setTimeout(() => toast.remove(), 180);
        };

        toast.querySelector('.ns-toast-close')?.addEventListener('click', close);
        stack.appendChild(toast);
        window.setTimeout(close, 4000);
    };

    const pollPaymentStatus = async () => {
        if (!statusUrl || isResolved || !backdrop.classList.contains('is-open')) {
            return;
        }

        try {
            const response = await fetch(statusUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                cache: 'no-store',
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const payload = await response.json();

            if (payload?.paid) {
                isResolved = true;

                if (statusText) {
                    statusText.textContent = 'Giao dich da duoc xac nhan.';
                }

                closeQr();
                showToast('Nap tien thanh cong, coin da duoc cong vao tai khoan.');
                return;
            }

            if (statusText) {
                statusText.textContent = 'Dang cho webhook xac nhan giao dich...';
            }
        } catch (error) {
            if (statusText) {
                statusText.textContent = 'Dang kiem tra giao dich, vui long cho trong giay lat...';
            }
        }

        pollingTimer = window.setTimeout(pollPaymentStatus, 5000);
    };

    document.querySelectorAll('[data-payment-qr-close]').forEach((button) => {
        button.addEventListener('click', closeQr);
    });

    backdrop.addEventListener('click', (event) => {
        if (event.target === backdrop) {
            closeQr();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && backdrop.classList.contains('is-open')) {
            closeQr();
        }
    });

    document.querySelectorAll('[data-copy-value]').forEach((button) => {
        button.addEventListener('click', async () => {
            const value = button.dataset.copyValue || '';

            try {
                await navigator.clipboard.writeText(value);
                button.textContent = 'OK';
                setTimeout(() => {
                    button.textContent = 'Copy';
                }, 1200);
            } catch (error) {
                const input = document.createElement('input');
                input.value = value;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                input.remove();
            }
        });
    });

    pollPaymentStatus();
});

document.addEventListener('DOMContentLoaded', () => {
    const backdrop = document.querySelector('[data-auth-backdrop]');

    if (!backdrop) {
        return;
    }

    const modals = Array.from(document.querySelectorAll('[data-auth-modal]'));

    const openModal = (name) => {
        backdrop.classList.add('is-open');
        backdrop.setAttribute('aria-hidden', 'false');
        document.body.classList.add('auth-open');

        modals.forEach((modal) => {
            modal.classList.toggle('is-active', modal.dataset.authModal === name);
        });
    };

    const closeModal = () => {
        backdrop.classList.remove('is-open');
        backdrop.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('auth-open');
        modals.forEach((modal) => modal.classList.remove('is-active'));

        if (window.location.pathname === '/login' || window.location.pathname === '/register' || window.location.pathname === '/forgot-password') {
            history.replaceState(null, '', '/');
        }
    };

    document.querySelectorAll('[data-auth-open]').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            openModal(link.dataset.authOpen);
        });
    });

    document.querySelectorAll('[data-auth-switch]').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            openModal(link.dataset.authSwitch);
        });
    });

    document.querySelectorAll('[data-auth-close]').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            closeModal();
        });
    });

    backdrop.addEventListener('click', (event) => {
        if (event.target === backdrop) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && backdrop.classList.contains('is-open')) {
            closeModal();
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const profileModals = Array.from(document.querySelectorAll('[data-profile-modal]'));

    if (profileModals.length === 0) {
        return;
    }

    const openProfileModal = (name) => {
        profileModals.forEach((modal) => {
            const isActive = modal.dataset.profileModal === name;
            modal.classList.toggle('is-open', isActive);
            modal.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        });
        document.body.classList.add('auth-open');
    };

    const closeProfileModal = () => {
        profileModals.forEach((modal) => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        });
        document.body.classList.remove('auth-open');

        if (window.location.search.includes('modal=')) {
            history.replaceState(null, '', '/profile?tab=settings');
        }
    };

    document.querySelectorAll('[data-profile-modal-open]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            openProfileModal(button.dataset.profileModalOpen);
        });
    });

    document.querySelectorAll('[data-profile-modal-close]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            closeProfileModal();
        });
    });

    profileModals.forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeProfileModal();
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && profileModals.some((modal) => modal.classList.contains('is-open'))) {
            closeProfileModal();
        }
    });

});
