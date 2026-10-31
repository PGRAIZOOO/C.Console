<?php
include('./includes/config.php');
include('./includes/functions.php');
include('./includes/docs.php');

$title = 'Terms of Service';
include('./includes/header.php');
doc_open('Terms of Service', 'The rules and conditions for browsing, ordering and reviewing games at ' . SHOP_NAME . '.', [
    'acceptance' => 'Acceptance of terms',
    'accounts' => 'Accounts and responsibilities',
    'listings' => 'Products, pricing and availability',
    'ordering' => 'Ordering',
    'payment' => 'Payment',
    'cancellation' => 'Cancellation, voids and refunds',
    'reviews' => 'Reviews and user content',
    'prohibited' => 'Prohibited activities',
    'ip' => 'Intellectual property',
    'liability' => 'Limitation of liability',
    'suspension' => 'Suspension and termination',
    'changes' => 'Changes to these terms',
    'law' => 'Governing law',
    'contact' => 'Contact',
]);
?>
<section id="acceptance">
    <h2>Acceptance of terms</h2>
    <p>These terms govern your use of the <?= e(SHOP_NAME) ?> website and the orders you place through it. By browsing the site, creating an account or placing an order, you agree to these terms and to our <a href="<?= e(url('privacy.php')) ?>">Privacy Policy</a>. If you do not agree, please do not use the website.</p>
</section>

<section id="accounts">
    <h2>Accounts and responsibilities</h2>
    <ul>
        <li>You need an account to check out, view your orders and write reviews. Browsing games and filling a cart do not require one.</li>
        <li>When you register you must give a full name, a unique username (3 to 50 letters, numbers, dots or underscores), a valid email address that is not already registered, and a password of at least six characters.</li>
        <li>To receive an order you must also add a contact number and delivery address to your profile. Keep this information accurate and up to date.</li>
        <li>You are responsible for keeping your password confidential and for everything done through your account. Tell us promptly if you suspect unauthorised use.</li>
        <li>Administrator accounts are for managing the store and cannot place customer orders.</li>
    </ul>
</section>

<section id="listings">
    <h2>Products, pricing and availability</h2>
    <ul>
        <li><?= e(SHOP_NAME) ?> lists PlayStation 5 games only. Each listing shows the title, studio, genre, game code, description, images and price.</li>
        <li>All prices are in Philippine pesos (₱) and are the prices shown when you place your order. Price changes do not affect an order already placed.</li>
        <li>Availability follows the stock the store holds. A game is shown as In Stock, Low Stock (a small number remaining) or Out of Stock, and the quantity you can add to a cart is limited to the stock available.</li>
        <li>We try to keep descriptions, images and prices accurate, but errors can occur. We may correct an error or remove a listing at any time, including after you have placed an order, in which case we will contact you.</li>
    </ul>
</section>

<section id="ordering">
    <h2>Ordering</h2>
    <ol>
        <li><strong>Add to cart.</strong> Choose the games and quantities you want. Your cart is held for your browsing session and is not a reservation of stock.</li>
        <li><strong>Check out.</strong> Sign in, review your delivery details and items, and choose the date you need your order, from today up to one year ahead.</li>
        <li><strong>Place the order.</strong> Placing the order creates it with the status <em>Pending</em> and sets aside the stock for it. When the store's email service is configured, a receipt is sent to your account email.</li>
        <li><strong>Confirmation.</strong> The store reviews pending orders and changes them to <em>Confirmed</em>. An order is not accepted by the store until it is confirmed.</li>
    </ol>
    <p>You can follow every order's status on the My Orders page. The store may decline or cancel an order, for example when stock cannot be supplied or details cannot be verified.</p>
</section>

<section id="payment">
    <h2>Payment</h2>
    <p>The website does not collect card numbers or process online payments. An order records what you want and the total in pesos. Arrangements for paying for a confirmed order are made with the store directly, and any discount applied to an order is shown in its totals. We will never ask you for a card number or password by email or through the contact form.</p>
</section>

<section id="cancellation">
    <h2>Cancellation, voids and refunds</h2>
    <ul>
        <li><strong>Cancelling an order.</strong> You can cancel your own order from My Orders or the order page while it is still <em>Pending</em>. The reserved stock is returned and, because the order was never confirmed, you will not be charged.</li>
        <li><strong>After confirmation.</strong> A confirmed order can no longer be cancelled online. Contact the store if you need to change it. The store may void a confirmed order, which makes it invalid and returns the stock.</li>
        <li><strong>Refunds and returns.</strong> The website has no automated refund process. Requests to return an item or to be refunded for a payment already made are handled by the store on a case-by-case basis through the <a href="<?= e(url('contact.php')) ?>">contact page</a>, and any outcome will be confirmed to you directly.</li>
    </ul>
</section>

<section id="reviews">
    <h2>Reviews and user content</h2>
    <ul>
        <li>You can review a game once you have a confirmed order that includes it, with one review per game. A review has a star rating from 1 to 5, a title of 3 to 100 characters and text of 5 to 1,000 characters. You may edit or delete your own review at any time.</li>
        <li>You may attach up to four photos to a review. Photos must be JPG, PNG or WEBP files of 3 MB or less, and you may only upload images you have the right to share.</li>
        <li>Offensive words are masked automatically, and HTML or script tags are not allowed. The store may remove reviews or photos that break these terms.</li>
        <li>By posting a review you give <?= e(SHOP_NAME) ?> permission to display it, with your name and profile photo, on the website for as long as the review remains.</li>
    </ul>
</section>

<section id="prohibited">
    <h2>Prohibited activities</h2>
    <p>You agree not to:</p>
    <ul>
        <li>Provide false information, impersonate another person or use another person's account.</li>
        <li>Attempt to gain unauthorised access to the website, other accounts or the database, or to interfere with how the site works.</li>
        <li>Upload malicious code, offensive, unlawful or infringing content, or content that exposes someone else's private information.</li>
        <li>Use bots, scrapers or other automated tools to collect data or place orders in bulk.</li>
        <li>Place orders you do not intend to complete, or abuse the cancellation feature to hold stock.</li>
        <li>Post reviews that are fake, paid for, or about a game you did not buy.</li>
        <li>Use the website for any unlawful purpose.</li>
    </ul>
</section>

<section id="ip">
    <h2>Intellectual property</h2>
    <p>The <?= e(SHOP_NAME) ?> name, logo, page designs and original text belong to the store. Game titles, artwork, logos and trademarks shown on the website belong to their respective publishers, studios and platform holders, and are shown only to identify products for sale. See the <a href="<?= e(url('disclaimer.php')) ?>">Disclaimer</a> for more. You may not copy, republish or sell any part of the site without permission.</p>
</section>

<section id="liability">
    <h2>Limitation of liability</h2>
    <p>The website and its content are provided on an "as is" and "as available" basis. To the extent permitted by law, <?= e(SHOP_NAME) ?> is not liable for indirect or consequential losses, for temporary interruptions or errors on the website, or for loss caused by events outside our reasonable control. Nothing in these terms limits any right you have under Philippine consumer protection law that cannot be waived.</p>
</section>

<section id="suspension">
    <h2>Suspension and termination</h2>
    <p>The store may deactivate or suspend an account, cancel orders or remove content if these terms are broken, if the account is used in a way that puts the site or other customers at risk, or if required by law. A deactivated account cannot sign in. You may stop using the website at any time and may ask us to review your account through the contact page.</p>
</section>

<section id="changes">
    <h2>Changes to these terms</h2>
    <p>We may revise these terms as the website and our services change. The "last updated" date shows the latest revision. Changes apply to your use of the website after they are posted and do not change orders already placed. Continuing to use the site means you accept the revised terms.</p>
</section>

<section id="law">
    <h2>Governing law</h2>
    <p>These terms are intended to be read under the laws of the Republic of the Philippines. Any dispute that cannot be settled informally should be raised with the store first through the contact details below.</p>
</section>

<section id="contact">
    <h2>Contact</h2>
    <p>Questions about these terms can be sent to:</p>
    <div class="contact-card">
        <span>Email</span><strong><a href="mailto:<?= e(CONTACT_EMAIL) ?>"><?= e(CONTACT_EMAIL) ?></a></strong>
        <span>Online</span><strong><a href="<?= e(url('contact.php')) ?>">Contact form</a></strong>
        <span>Store hours</span><strong><?= e(CONTACT_HOURS) ?></strong>
    </div>
</section>
<?php
doc_close();
include('./includes/footer.php');
