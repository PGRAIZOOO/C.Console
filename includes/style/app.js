document.addEventListener('submit', function (event) {
    var message = event.target.getAttribute('data-confirm');
    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-add-row]');
    if (!button) {
        return;
    }
    var container = document.querySelector(button.getAttribute('data-add-row'));
    var last = container.lastElementChild;
    var copy = last.cloneNode(true);
    copy.querySelectorAll('select').forEach(function (select) {
        select.selectedIndex = 0;
    });
    copy.querySelectorAll('input').forEach(function (input) {
        input.value = input.defaultValue;
    });
    container.appendChild(copy);
    container.dispatchEvent(new Event('change', { bubbles: true }));
});

(function () {
    var form = document.querySelector('[data-pos]');
    if (!form) {
        return;
    }
    var format = function (amount) {
        return '₱' + amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };
    var set = function (name, amount) {
        form.closest('.layout').querySelector('[data-pos-' + name + ']').textContent = format(amount);
    };
    var recalc = function () {
        var subtotal = 0;
        form.querySelectorAll('.pos-row').forEach(function (row) {
            var select = row.querySelector('select');
            var qty = row.querySelector('input[name="qty[]"]');
            if (select && qty && select.value !== '') {
                var price = parseFloat(select.options[select.selectedIndex].getAttribute('data-price')) || 0;
                subtotal += price * (parseInt(qty.value, 10) || 0);
            }
        });
        var type = form.querySelector('[name="discount_type"]').value;
        var value = parseFloat(form.querySelector('[name="discount_value"]').value) || 0;
        var discount = 0;
        if (type === 'Percentage') {
            discount = subtotal * value / 100;
        } else if (type === 'Fixed') {
            discount = value;
        }
        discount = Math.round(Math.min(Math.max(discount, 0), subtotal) * 100) / 100;
        var total = Math.round((subtotal - discount) * 100) / 100;
        set('subtotal', subtotal);
        set('discount', discount);
        set('total', total);
    };
    form.addEventListener('input', recalc);
    form.addEventListener('change', recalc);
    recalc();
})();

document.addEventListener('click', function (event) {
    var main = document.querySelector('[data-gallery-main]');
    if (!main) {
        return;
    }
    var thumbs = Array.prototype.slice.call(document.querySelectorAll('.gallery-thumb'));
    var show = function (index) {
        var thumb = thumbs[index];
        main.src = thumb.getAttribute('data-gallery-src');
        thumbs.forEach(function (item) {
            item.classList.toggle('active', item === thumb);
        });
    };
    var thumb = event.target.closest('[data-gallery-src]');
    if (thumb) {
        show(thumbs.indexOf(thumb));
        return;
    }
    var arrow = event.target.closest('[data-gallery-step]');
    if (arrow && thumbs.length > 1) {
        var current = thumbs.findIndex(function (item) {
            return item.classList.contains('active');
        });
        var step = parseInt(arrow.getAttribute('data-gallery-step'), 10);
        show((Math.max(current, 0) + step + thumbs.length) % thumbs.length);
    }
});

(function () {
    var closeAll = function (except) {
        document.querySelectorAll('.profile-menu.open').forEach(function (menu) {
            if (menu !== except) {
                menu.classList.remove('open');
            }
        });
    };
    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('.profile-trigger');
        if (trigger) {
            var menu = trigger.closest('.profile-menu');
            if (window.matchMedia('(hover: none)').matches && !menu.classList.contains('open')) {
                event.preventDefault();
                closeAll(menu);
                menu.classList.add('open');
            }
            return;
        }
        if (!event.target.closest('.profile-menu')) {
            closeAll(null);
        }
    });
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }
        closeAll(null);
        if (document.activeElement && document.activeElement.closest('.profile-menu')) {
            document.activeElement.blur();
        }
    });
})();

(function () {
    var show = function (gallery, index) {
        var slides = gallery.querySelectorAll('[data-review-slide]');
        var dots = gallery.querySelectorAll('[data-review-dot]');
        var label = gallery.querySelector('[data-review-index]');
        if (!slides.length) {
            return;
        }
        index = (index + slides.length) % slides.length;
        slides.forEach(function (slide, i) {
            slide.classList.toggle('active', i === index);
        });
        dots.forEach(function (dot, i) {
            dot.classList.toggle('active', i === index);
        });
        if (label) {
            label.textContent = index + 1;
        }
    };
    var current = function (gallery) {
        var slides = Array.prototype.slice.call(gallery.querySelectorAll('[data-review-slide]'));
        return Math.max(slides.findIndex(function (slide) {
            return slide.classList.contains('active');
        }), 0);
    };
    document.addEventListener('click', function (event) {
        var arrow = event.target.closest('[data-review-step]');
        var dot = event.target.closest('[data-review-dot]');
        var control = arrow || dot;
        if (!control) {
            return;
        }
        var gallery = control.closest('[data-review-gallery]');
        if (!gallery) {
            return;
        }
        if (arrow) {
            show(gallery, current(gallery) + parseInt(arrow.getAttribute('data-review-step'), 10));
        } else {
            show(gallery, parseInt(dot.getAttribute('data-review-dot'), 10));
        }
    });
    document.addEventListener('keydown', function (event) {
        var gallery = event.target.closest && event.target.closest('[data-review-gallery]');
        if (!gallery || !gallery.querySelector('[data-review-step]')) {
            return;
        }
        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            show(gallery, current(gallery) - 1);
        } else if (event.key === 'ArrowRight') {
            event.preventDefault();
            show(gallery, current(gallery) + 1);
        }
    });
})();

document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-stepper-target]');
    if (!button) {
        return;
    }
    var input = document.querySelector(button.getAttribute('data-stepper-target'));
    if (!input) {
        return;
    }
    var value = parseInt(input.value, 10);
    if (isNaN(value)) {
        value = 0;
    }
    var step = parseInt(button.getAttribute('data-stepper-step'), 10) || 0;
    input.value = Math.min(Math.max(value + step, 0), 1000000);
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.focus();
});
