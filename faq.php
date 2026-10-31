<?php
include('./includes/config.php');
include('./includes/functions.php');
include('./includes/docs.php');

$contact = '<a href="' . e(url('contact.php')) . '">contact page</a>';

$faq = [
    'accounts' => ['Accounts and login', [
        ['How do I create an account?', 'Select <strong>Register</strong> at the top of any page and enter your full name, a username, your email address and a password of at least six characters. A contact number and profile photo are optional. You can start shopping straight away, and a welcome email is sent if the store\'s email service is configured.'],
        ['Can I sign in with my email address?', 'Yes. The sign-in form accepts either your username or your email address, together with your password.'],
        ['Do I need an account to browse or add games to my cart?', 'No. Anyone can browse the catalogue and fill a cart. You need to sign in to check out, view your orders and write reviews. Items already in your cart are kept when you sign in.'],
        ['How do I update my details or profile photo?', 'Open <strong>Profile</strong> after signing in. You can change your name, email, contact number, delivery address and photo (JPG, PNG or WEBP, up to 3 MB). Your email must not already be used by another account.'],
        ['I forgot my password. What can I do?', 'The website does not have an automatic password reset yet. Send us a message through the ' . $contact . ' from the email address on your account and we will help you regain access.'],
        ['Why was I signed out?', 'For your security a sign-in lasts eight hours. After that, or after you sign out, you will need to sign in again. If your account has been deactivated you will see a message when you try to sign in.'],
    ]],
    'browsing' => ['Browsing games and availability', [
        ['What does C.Console sell?', 'PlayStation 5 games only. Every listing shows the title, studio, genre, game code, price, images and a description.'],
        ['How do I find a specific game?', 'Open <strong>Shop</strong> and search by title, studio, genre, game code or price. You can narrow the list by genre, availability and price range, and sort by newest, title or price.'],
        ['What do In Stock, Low Stock and Out of Stock mean?', '<strong>In Stock</strong> means there are plenty of copies. <strong>Low Stock</strong> means only a few are left, and the number remaining is shown. <strong>Out of Stock</strong> means no copies are available, so the game cannot be added to a cart.'],
        ['Why can I only add some copies of a game?', 'The quantity in your cart cannot be more than the copies the store holds. If you ask for more, the cart is adjusted to the amount available.'],
        ['Are prices in pesos?', 'Yes. Every price is in Philippine pesos (₱) and the price on your order is the price at the time you placed it.'],
    ]],
    'orders' => ['Ordering, status and cancellation', [
        ['How do I place an order?', 'Add games to your cart, open the cart and choose <strong>Checkout</strong>. Confirm your delivery details, pick the date you need the order and select <strong>Place order</strong>. Your contact number and delivery address must be saved in your profile first.'],
        ['What is the "date needed"?', 'It is the date by which you would like to receive your order. You can choose any date from today up to one year ahead.'],
        ['What do the order statuses mean?', '<strong>Pending</strong>: your order has been placed and is waiting for the store. <strong>Confirmed</strong>: the store has accepted it. <strong>Cancelled</strong>: the order was cancelled before confirmation. <strong>Voided</strong>: a confirmed order was made invalid by the store.'],
        ['Where can I check my order?', 'Open <strong>My Orders</strong> to see every order with its date, items, total and status, and select an order for the full breakdown.'],
        ['Can I cancel an order?', 'Yes, while it is still <strong>Pending</strong>. Use <strong>Cancel order</strong> on My Orders or the order page. The stock is returned and you will not be charged. Once an order is Confirmed it can no longer be cancelled online, so please ' . $contact . ' if you need help.'],
        ['How do I pay?', 'The website does not take card or online payments. Placing an order records your request and its total. The store arranges payment for confirmed orders directly with you, so please wait for confirmation before sending any money, and never share card details through the website or contact form.'],
        ['What about refunds and returns?', 'There is no automatic refund process. Cancelling a pending order means you are not charged. For anything else, send us the order number through the ' . $contact . ' and we will review it with you.'],
    ]],
    'reviews' => ['Reviews and photos', [
        ['Who can write a review?', 'Customers who have a <strong>Confirmed</strong> order that includes the game. You can write one review per game and edit or delete it later from the game\'s page.'],
        ['What are the rules for a review?', 'Give a rating from 1 to 5 stars, a title of 3 to 100 characters and review text of 5 to 1,000 characters. HTML and script tags are not allowed, and offensive words are masked with asterisks automatically.'],
        ['Can I add photos to my review?', 'Yes, up to four photos per review. Each must be a JPG, PNG or WEBP file of 3 MB or less. You can add or remove photos when you edit the review.'],
        ['Why can\'t I see the review form for a game?', 'You may not yet have a confirmed order for that game, you may have reviewed it already, or you are not signed in. Administrator accounts do not place customer orders.'],
    ]],
    'help' => ['Troubleshooting', [
        ['I did not receive an order email.', 'Check your spam folder first and make sure the email on your profile is correct. Emails depend on the store\'s email service, so they can be delayed. Your order is always saved and visible on My Orders even if no email arrives.'],
        ['My photo or review image will not upload.', 'Files must be JPG, PNG or WEBP and no larger than 3 MB each, and a review can hold up to four photos. Try resizing the image or choosing a different file.'],
        ['The page says my session expired.', 'Refresh the page, sign in again if asked, and repeat what you were doing. Sessions end after eight hours or if your browser blocks cookies, which the site needs for sign-in and your cart.'],
        ['Checkout will not let me place my order.', 'Make sure your profile has both a contact number and a delivery address, the date needed is valid, and every game in your cart is still in stock. A message at the top of the page explains what to fix.'],
        ['I cannot find the answer to my question.', 'Send us a message through the ' . $contact . ' with as much detail as possible, such as your username and order number, and we will reply by email.'],
    ]],
];

$toc = [];
foreach ($faq as $id => [$label]) {
    $toc[$id] = $label;
}

$title = 'FAQ';
include('./includes/header.php');
doc_open('Frequently Asked Questions', 'Quick answers about accounts, games, orders, reviews and troubleshooting.', $toc, false);
?>
<?php foreach ($faq as $id => [$label, $items]): ?>
    <section id="<?= e($id) ?>">
        <h2><?= e($label) ?></h2>
        <div class="faq-list">
            <?php foreach ($items as [$question, $answer]): ?>
                <details class="faq-item">
                    <summary><?= e($question) ?></summary>
                    <div class="faq-answer"><p><?= $answer ?></p></div>
                </details>
            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>
<section class="faq-cta">
    <h2>Still need help?</h2>
    <p>Our team replies to messages during store hours (<?= e(CONTACT_HOURS) ?>).</p>
    <p><a class="btn btn-primary" href="<?= e(url('contact.php')) ?>">Contact us</a> <a class="btn btn-ghost" href="<?= e(url('terms.php')) ?>">Read the Terms of Service</a></p>
</section>
<?php
doc_close(false);
include('./includes/footer.php');
