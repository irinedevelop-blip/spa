(function () {
    "use strict";

    /* =====================================================
       СТРАНИЦА ПОКУПКИ СЕРТИФИКАТА
    ===================================================== */

    function formatPrice(value) {
        value = parseFloat(value || 0);

        try {
            return new Intl.NumberFormat("ru-RU").format(value) +
                " " +
                (window.MSCERT && MSCERT.currency
                    ? MSCERT.currency
                    : "₸");
        } catch (error) {
            return value + " ₸";
        }
    }


    function initCertificateForm() {
        const forms = document.querySelectorAll(".mscert-form");

        forms.forEach(function (form) {
            if (form.dataset.mscertReady === "yes") {
                return;
            }

            form.dataset.mscertReady = "yes";

            const amountButtons =
                form.querySelectorAll("[data-amount]");

            const finalAmount =
                form.querySelector("[data-mscert-final-amount]");

            const customAmount =
                form.querySelector("[data-mscert-custom-amount]");

            const firstInput =
                form.querySelector("[data-mscert-first]");

            const lastInput =
                form.querySelector("[data-mscert-last]");

            const messageInput =
                form.querySelector("[data-mscert-message]");

            const designSelect =
                form.querySelector("[data-mscert-design]");

            const shell = form.closest(".mscert-shell");

            const preview = shell
                ? shell.querySelector("[data-mscert-preview]")
                : null;

            const previewAmount = shell
                ? shell.querySelector(
                    "[data-mscert-preview-amount]"
                )
                : null;

            const previewName = shell
                ? shell.querySelector(
                    "[data-mscert-preview-name]"
                )
                : null;

            const previewMessage = shell
                ? shell.querySelector(
                    "[data-mscert-preview-message]"
                )
                : null;


            function setAmount(amount) {
                amount = parseFloat(amount || 0);

                if (!amount) {
                    amount = 10000;
                }

                const min =
                    window.MSCERT && MSCERT.minAmount
                        ? parseFloat(MSCERT.minAmount)
                        : 1000;

                const max =
                    window.MSCERT && MSCERT.maxAmount
                        ? parseFloat(MSCERT.maxAmount)
                        : 1000000;

                amount = Math.max(min, Math.min(max, amount));

                if (finalAmount) {
                    finalAmount.value = amount;
                }

                if (previewAmount) {
                    previewAmount.textContent =
                        formatPrice(amount);
                }
            }


            function updateName() {
                const first = firstInput
                    ? firstInput.value.trim()
                    : "";

                const last = lastInput
                    ? lastInput.value.trim()
                    : "";

                const fullName =
                    (first + " " + last).trim();

                if (previewName) {
                    previewName.textContent =
                        fullName || "Имя Получателя";
                }
            }


            function updateMessage() {
                if (!previewMessage || !messageInput) {
                    return;
                }

                previewMessage.textContent =
                    messageInput.value.trim() ||
                    "Время для себя — в премиальном формате";
            }


            function updateDesign() {
                if (!preview || !designSelect) {
                    return;
                }

                preview.setAttribute(
                    "data-design",
                    designSelect.value || "gold"
                );
            }


            amountButtons.forEach(function (button) {
                button.addEventListener(
                    "click",
                    function () {
                        amountButtons.forEach(function (item) {
                            item.classList.remove("is-active");
                        });

                        button.classList.add("is-active");

                        if (customAmount) {
                            customAmount.value = "";
                        }

                        setAmount(button.dataset.amount);
                    }
                );
            });


            if (customAmount) {
                customAmount.addEventListener(
                    "input",
                    function () {
                        amountButtons.forEach(function (item) {
                            item.classList.remove("is-active");
                        });

                        setAmount(customAmount.value);
                    }
                );
            }

            if (firstInput) {
                firstInput.addEventListener(
                    "input",
                    updateName
                );
            }

            if (lastInput) {
                lastInput.addEventListener(
                    "input",
                    updateName
                );
            }

            if (messageInput) {
                messageInput.addEventListener(
                    "input",
                    updateMessage
                );
            }

            if (designSelect) {
                designSelect.addEventListener(
                    "change",
                    updateDesign
                );
            }

            setAmount(
                finalAmount && finalAmount.value
                    ? finalAmount.value
                    : 10000
            );

            updateName();
            updateMessage();
            updateDesign();
        });
    }


    /* =====================================================
       CHECKOUT: ЭЛЕКТРОННЫЙ / ПЕЧАТНЫЙ

       Ничего не перемещает.
       Работает с точными полями из PHP:
       mscert_delivery_type
       billing_address_1
       billing_address_2
    ===================================================== */

    function initCheckoutDelivery() {
        const checkout = document.querySelector(
            "form.checkout.woocommerce-checkout"
        );

        if (!checkout) {
            return;
        }

        const deliveryField = document.getElementById(
            "mscert_delivery_type_field"
        );

        const deliveryRadios =
            checkout.querySelectorAll(
                'input[name="mscert_delivery_type"]'
            );

        const addressOneRow = document.getElementById(
            "billing_address_1_field"
        );

        const addressTwoRow = document.getElementById(
            "billing_address_2_field"
        );

        const addressOne = document.getElementById(
            "billing_address_1"
        );

        const addressTwo = document.getElementById(
            "billing_address_2"
        );

        if (!deliveryField || !deliveryRadios.length) {
            return;
        }

        deliveryField.classList.add(
            "mscert-delivery-ready"
        );

        const selected = checkout.querySelector(
            'input[name="mscert_delivery_type"]:checked'
        );

        const deliveryType = selected
            ? selected.value
            : "email";

        const needsAddress =
            deliveryType === "karaganda";

        deliveryField.classList.toggle(
            "is-email",
            !needsAddress
        );

        deliveryField.classList.toggle(
            "is-karaganda",
            needsAddress
        );

        [addressOneRow, addressTwoRow].forEach(
            function (row) {
                if (!row) {
                    return;
                }

                row.classList.toggle(
                    "mscert-address-hidden",
                    !needsAddress
                );

                row.setAttribute(
                    "aria-hidden",
                    needsAddress ? "false" : "true"
                );
            }
        );

        if (addressOne) {
            addressOne.disabled = !needsAddress;
            addressOne.required = needsAddress;

            if (needsAddress) {
                addressOne.setAttribute(
                    "aria-required",
                    "true"
                );
            } else {
                addressOne.removeAttribute(
                    "aria-required"
                );
            }
        }

        if (addressTwo) {
            addressTwo.disabled = !needsAddress;
        }

        deliveryRadios.forEach(function (radio) {
            const label = document.querySelector(
                'label[for="' + radio.id + '"]'
            );

            if (label) {
                label.classList.toggle(
                    "is-selected",
                    radio.checked
                );
            }
        });
    }


    function renameCheckoutHeadings() {
        const billingTitle = document.querySelector(
            ".woocommerce-billing-fields > h3"
        );

        const additionalTitle = document.querySelector(
            ".woocommerce-additional-fields > h3"
        );

        const orderTitle = document.getElementById(
            "order_review_heading"
        );

        if (billingTitle) {
            billingTitle.textContent =
                "Оформление подарка";
        }

        if (additionalTitle) {
            additionalTitle.textContent =
                "Пожелания к заказу";
        }

        if (orderTitle) {
            orderTitle.textContent =
                "Детали заказа";
        }
    }


    function initCheckout() {
        renameCheckoutHeadings();
        initCheckoutDelivery();
    }


    document.addEventListener(
        "change",
        function (event) {
            if (
                event.target.matches(
                    'input[name="mscert_delivery_type"]'
                )
            ) {
                initCheckoutDelivery();

                if (window.jQuery) {
                    jQuery(document.body).trigger(
                        "update_checkout"
                    );
                }
            }
        }
    );


    document.addEventListener(
        "DOMContentLoaded",
        function () {
            initCertificateForm();
            initCheckout();
        }
    );


    window.addEventListener(
        "load",
        function () {
            initCertificateForm();
            initCheckout();
        }
    );


    if (window.jQuery) {
        jQuery(document.body).on(
            "updated_checkout",
            function () {
                window.setTimeout(
                    initCheckout,
                    30
                );
            }
        );
    }
})();
