<?php
include('./includes/config.php');
include('./includes/functions.php');
include('./includes/docs.php');

$title = 'Disclaimer';
include('./includes/header.php');
doc_open('Disclaimer', 'The nature and limits of the information and services provided by ' . SHOP_NAME . '.', [
    'general' => 'General information',
    'trademarks' => 'Trademarks and intellectual property',
    'listings' => 'Descriptions, images and pricing',
    'availability' => 'Product availability',
    'reviews' => 'Customer reviews',
    'interruptions' => 'Website interruptions',
    'thirdparty' => 'Third-party services and links',
    'affiliation' => 'No affiliation',
    'contact' => 'Questions',
]);
?>
<section id="general">
    <h2>General information</h2>
    <p>The content of the <?= e(SHOP_NAME) ?> website is provided for general information about the PlayStation 5 games the store sells and to let customers place orders. We make reasonable efforts to keep it accurate and current, but we do not guarantee that it is complete, error-free or suitable for any particular purpose. Please read this disclaimer together with our <a href="<?= e(url('terms.php')) ?>">Terms of Service</a> and <a href="<?= e(url('privacy.php')) ?>">Privacy Policy</a>.</p>
</section>

<section id="trademarks">
    <h2>Trademarks and intellectual property</h2>
    <p>All game titles, cover art, screenshots, logos, studio names, character names and trademarks that appear on this website are the property of their respective owners, including game publishers, developers and platform holders. They are used on this site only to identify and describe the products offered for sale. The appearance of any name or image does not imply endorsement of, or by, <?= e(SHOP_NAME) ?>.</p>
    <p>PlayStation, PS5 and related names are trademarks of their owners. <?= e(SHOP_NAME) ?> is an independent shop and is not an official PlayStation or publisher storefront.</p>
</section>

<section id="listings">
    <h2>Descriptions, images and pricing</h2>
    <ul>
        <li>Game descriptions are short summaries intended to help you choose. They may not cover every feature, mode or requirement of a title.</li>
        <li>Images are for illustration. The packaging, artwork or edition you receive may look different from what is shown.</li>
        <li>Prices are shown in Philippine pesos and can change at any time without notice. The price at the time you place an order is the price recorded on that order.</li>
        <li>If a listing contains an obvious error, we may correct it and contact you about any affected order.</li>
    </ul>
</section>

<section id="availability">
    <h2>Product availability</h2>
    <p>Stock levels on the website reflect the quantity the store holds when the page is displayed. Because other customers may order at the same time, a game can sell out between the moment you view it and the moment you check out. The In Stock, Low Stock and Out of Stock labels are guides, not guarantees, and an order is only accepted when the store changes its status to Confirmed.</p>
</section>

<section id="reviews">
    <h2>Customer reviews</h2>
    <p>Reviews and photos are the personal opinions of the customers who post them. They do not represent the views of <?= e(SHOP_NAME) ?> and we do not verify every statement made in them. Offensive wording is masked automatically and the store may remove content that breaks the rules, but we cannot guarantee that every review is accurate.</p>
</section>

<section id="interruptions">
    <h2>Website interruptions</h2>
    <p>We aim to keep the website available but cannot promise uninterrupted or error-free access. The site may be unavailable because of maintenance, technical faults, internet problems or events beyond our control. We are not responsible for loss that results from such interruptions, including failed or delayed orders, though you can contact the store to check the state of any order.</p>
    <p>Emails such as receipts and status updates depend on an email service and on your mailbox, and may be delayed, filtered as spam or not delivered. An order's status can always be checked on the My Orders page.</p>
</section>

<section id="thirdparty">
    <h2>Third-party services and links</h2>
    <p>The website may rely on third-party services, such as an email delivery provider, and may in future link to external sites. We do not control those services or sites and are not responsible for their content, availability, security or privacy practices. Following an external link is at your own risk, and you should read the terms and privacy policy of any site you visit.</p>
</section>

<section id="affiliation">
    <h2>No affiliation</h2>
    <p>Unless stated otherwise, <?= e(SHOP_NAME) ?> is not affiliated with, sponsored by or endorsed by any game publisher, studio or console manufacturer whose products appear in its catalogue.</p>
</section>

<section id="contact">
    <h2>Questions</h2>
    <p>If you believe content on this site infringes your rights, or you spot an error in a listing, please tell us so we can review it.</p>
    <div class="contact-card">
        <span>Email</span><strong><a href="mailto:<?= e(CONTACT_EMAIL) ?>"><?= e(CONTACT_EMAIL) ?></a></strong>
        <span>Online</span><strong><a href="<?= e(url('contact.php')) ?>">Contact form</a></strong>
    </div>
</section>
<?php
doc_close();
include('./includes/footer.php');
