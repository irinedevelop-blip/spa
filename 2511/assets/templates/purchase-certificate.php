<?php
/**
 * MEDI SIAM SPA — страница покупки сертификата.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (empty($product_id)) {
    return;
}
?>

<section class="mscert-page">
    <div class="mscert-shell">

        <!-- =============================================
             ПРЕДПРОСМОТР
        ============================================== -->

        <div
            class="mscert-art-preview"
            data-mscert-preview
            data-design="gold"
        >
            <div class="mscert-art-preview__shadow"></div>

            <div class="mscert-art-preview__card">
                <div class="mscert-art-preview__light"></div>

                <div class="mscert-art-preview__emblem-window">
                    <img
                        class="mscert-art-preview__emblem"
                        src="<?php
                            echo esc_url(
                                MSCERT_URL
                                . 'assets/images/emblem.svg'
                            );
                        ?>"
                        alt=""
                    >

                    <div class="mscert-art-preview__emblem-mask"></div>
                </div>

                <div class="mscert-art-preview__title">
                    Подарочный сертификат
                </div>

                <div class="mscert-art-preview__nominal-label">
                    Номинал
                </div>

                <div
                    class="mscert-art-preview__amount"
                    data-mscert-preview-amount
                >
                    10 000 ₸
                </div>

                <div class="mscert-art-preview__recipient">
                    <span class="mscert-art-preview__recipient-prefix">
                        для
                    </span>

                    <span
                        class="mscert-art-preview__recipient-name"
                        data-mscert-preview-name
                    >
                        Имя Получателя
                    </span>
                </div>

                <div class="mscert-art-preview__recipient-line"></div>

                <img
                    class="mscert-art-preview__logo"
                    src="<?php
                        echo esc_url(
                            MSCERT_URL
                            . 'assets/images/logo.webp'
                        );
                    ?>"
                    alt="Medi Siam SPA"
                >

                <div class="mscert-art-preview__footer">
                    Время для себя —<br>
                    в премиальном формате<br>

                    <span>
                        spa.yourweb.kz
                    </span>
                </div>

                <div class="mscert-art-preview__number">
                    № будет создан после оплаты
                </div>
            </div>

            <div class="mscert-art-preview__caption">
                <span>Предпросмотр</span>

                <span>
                    Дизайн изменяется при выборе варианта
                </span>
            </div>
        </div>

        <!-- =============================================
             ФОРМА
        ============================================== -->

        <div class="mscert-card">
            <div class="mscert-kicker">
                Подарок, который запоминают
            </div>

            <h1 class="mscert-title">
                Покупка сертификата
            </h1>

            <p class="mscert-subtitle">
                Укажите получателя, сумму и пожелание.
                После оплаты сертификат будет создан
                автоматически.
            </p>

            <form
                class="mscert-form"
                method="post"
                action="<?php
                    echo esc_url(
                        admin_url('admin-post.php')
                    );
                ?>"
            >
                <?php
                wp_nonce_field(
                    'mscert_add_to_cart',
                    'mscert_nonce'
                );
                ?>

                <input
                    type="hidden"
                    name="action"
                    value="mscert_add_to_cart"
                >

                <input
                    type="hidden"
                    name="product_id"
                    value="<?php echo esc_attr($product_id); ?>"
                >

                <input
                    type="hidden"
                    name="amount"
                    value="10000"
                    data-mscert-final-amount
                >

                <div class="mscert-grid">
                    <label class="mscert-field">
                        <span>Имя получателя</span>

                        <input
                            type="text"
                            name="recipient_first_name"
                            placeholder="Например: Анна"
                            required
                            data-mscert-first
                        >
                    </label>

                    <label class="mscert-field">
                        <span>Фамилия получателя</span>

                        <input
                            type="text"
                            name="recipient_last_name"
                            placeholder="Например: Иванова"
                            required
                            data-mscert-last
                        >
                    </label>
                </div>

                <label class="mscert-field">
                    <span>От кого</span>

                    <input
                        type="text"
                        name="sender_name"
                        placeholder="Например: Александр"
                        data-mscert-sender
                    >
                </label>

                <div class="mscert-field">
                    <span>Сумма сертификата</span>

                    <div
                        class="mscert-amounts"
                        data-mscert-amounts
                    >
                        <button
                            type="button"
                            class="is-active"
                            data-amount="10000"
                        >
                            10 000
                        </button>

                        <button
                            type="button"
                            data-amount="15000"
                        >
                            15 000
                        </button>

                        <button
                            type="button"
                            data-amount="20000"
                        >
                            20 000
                        </button>

                        <button
                            type="button"
                            data-amount="30000"
                        >
                            30 000
                        </button>

                        <button
                            type="button"
                            data-amount="50000"
                        >
                            50 000
                        </button>
                    </div>

                    <input
                        type="number"
                        name="custom_amount"
                        placeholder="Или введите свою сумму"
                        min="<?php
                            echo esc_attr(
                                mscert_get_min_amount()
                            );
                        ?>"
                        max="<?php
                            echo esc_attr(
                                mscert_get_max_amount()
                            );
                        ?>"
                        data-mscert-custom-amount
                    >
                </div>

                <div class="mscert-grid">
                    <label class="mscert-field">
                        <span>Email получателя</span>

                        <input
                            type="email"
                            name="delivery_email"
                            placeholder="email@example.com"
                        >
                    </label>

                    <label class="mscert-field">
                        <span>Дата отправки</span>

                        <input
                            type="date"
                            name="delivery_date"
                        >
                    </label>
                </div>

                <label class="mscert-field">
                    <span>Дизайн сертификата</span>

                    <select
                        name="design"
                        data-mscert-design
                    >
                        <option value="gold">
                            Gold / Светлый премиум
                        </option>

                        <option value="dark">
                            Dark / Тёмный премиум
                        </option>

                        <option value="classic">
                            Classic / Минимализм
                        </option>
                    </select>
                </label>

                <label class="mscert-field">
                    <span>Пожелание</span>

                    <textarea
                        name="message"
                        rows="4"
                        placeholder="Напишите короткое пожелание"
                        data-mscert-message
                    ></textarea>
                </label>

                <button
                    class="mscert-submit"
                    type="submit"
                >
                    Добавить сертификат в корзину
                </button>

                 
            </form>
        </div>
    </div>
</section>
