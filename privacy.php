<?php
include('./includes/config.php');
include('./includes/functions.php');
include('./includes/docs.php');

$title = 'Privacy Policy';
include('./includes/header.php');
doc_open('Privacy Policy', 'How ' . SHOP_NAME . ' collects, uses, stores and protects your personal information.', [
    'overview' => 'Overview',
    'collect' => 'Information we collect',
    'how' => 'How we collect it',
    'use' => 'How we use it',
    'storage' => 'Storage and security',
    'cookies' => 'Cookies and sessions',
    'sharing' => 'Sharing and disclosure',
    'retention' => 'Data retention',
    'rights' => 'Your rights and choices',
    'children' => 'Children',
    'changes' => 'Changes to this policy',
    'contact' => 'Privacy contact',
]);
?>
<section id="overview">
    <h2>Overview</h2>
    <p><?= e(SHOP_NAME) ?> is an online store for PlayStation 5 games. This policy explains what personal information we handle when you browse the catalogue, create an account, place an order, write a review or send us a message, and the choices you have about that information.</p>
    <p>By using the website you acknowledge this policy. If you do not agree with it, please do not create an account or submit personal information through the site. Browsing the public catalogue does not require an account.</p>
</section>

<section id="collect">
    <h2>Information we collect</h2>
    <p>We only collect information that the store needs to run accounts, fulfil orders and answer questions. The table below lists what the website currently handles.</p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Category</th><th>What it includes</th><th>Why we need it</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Account details</strong></td>
                    <td>Full name, username, email address, password (stored as a hash) and an optional profile photo.</td>
                    <td>To create and secure your account and to sign you in.</td>
                </tr>
                <tr>
                    <td><strong>Contact information</strong></td>
                    <td>Contact number and delivery address, which you add to your profile.</td>
                    <td>To prepare and deliver your orders and to reach you about them.</td>
                </tr>
                <tr>
                    <td><strong>Orders and transactions</strong></td>
                    <td>Games ordered, quantities, prices at the time of purchase, order date, the date you need the order, discounts applied, order totals and order status. Orders recorded at the store may also include the cash amount handed over and the change given.</td>
                    <td>To process, confirm, cancel and keep a record of your orders, and to send receipts.</td>
                </tr>
                <tr>
                    <td><strong>Reviews</strong></td>
                    <td>Star rating, review title, review text and up to four photos per review.</td>
                    <td>To publish your feedback on games you have bought.</td>
                </tr>
                <tr>
                    <td><strong>Messages</strong></td>
                    <td>The name, email address, subject and message you enter in the contact form.</td>
                    <td>To read and answer your enquiry.</td>
                </tr>
                <tr>
                    <td><strong>Website usage</strong></td>
                    <td>Your shopping cart, a session token that keeps you signed in, and the standard technical data a web server receives with every request, such as your IP address and browser type.</td>
                    <td>To keep the site working, keep you signed in and protect it from misuse.</td>
                </tr>
            </tbody>
        </table>
    </div>
    <p>The website does not ask for payment card numbers or online wallet details, and it does not load advertising or third-party analytics trackers.</p>
</section>

<section id="how">
    <h2>How we collect it</h2>
    <ul>
        <li><strong>Directly from you</strong> when you register, edit your profile, check out, submit a review or send a message through the contact form.</li>
        <li><strong>Automatically</strong> as you use the site, through your session, your cart and ordinary web server logs.</li>
        <li><strong>From store staff</strong> when an administrator records an order on your behalf, updates an order's status or manages your account.</li>
    </ul>
</section>

<section id="use">
    <h2>How we use your information</h2>
    <ul>
        <li>Create and manage your account and verify who is signing in.</li>
        <li>Process orders, keep stock levels accurate and let you track an order from Pending to Confirmed, or cancel it while it is still pending.</li>
        <li>Send transactional emails such as the registration notice and order receipts or status updates, when the store's email service is configured.</li>
        <li>Display your reviews, with your name and profile photo, on the game's page.</li>
        <li>Respond to questions, complaints and requests sent through the contact form.</li>
        <li>Keep the website secure, prevent abuse and fix technical problems.</li>
        <li>Keep business records of orders and stock movements.</li>
    </ul>
    <p>We do not sell your personal information and we do not use it for automated decision-making or profiling.</p>
</section>

<section id="storage">
    <h2>Storage and security</h2>
    <p>Your information is stored in the store's database and, for uploaded photos, in the website's file storage. We use the following measures:</p>
    <ul>
        <li>Passwords are stored as salted one-way hashes (bcrypt). A password saved in an older format is converted to a hash the next time its owner signs in.</li>
        <li>Database queries that use your input are parameterised, and every form that changes data is protected by a security token.</li>
        <li>Sign-in sessions use cookies that are not readable by scripts, expire after eight hours and can be revoked by signing out.</li>
        <li>Uploaded images are checked for type and size, and their stored file names do not reveal the original names.</li>
        <li>Administrative pages can only be opened by accounts with the administrator role.</li>
    </ul>
    <p>No online service can promise absolute security. You can help by choosing a strong password, not sharing it, and signing out on shared devices. If you believe your account has been compromised, please contact us right away.</p>
</section>

<section id="cookies">
    <h2>Cookies and sessions</h2>
    <p>The website uses only the cookies it needs to work. A session cookie remembers your cart and sign-in state, and a small marker cookie helps the site notice when a sign-in has expired. Neither is used for advertising. If you block cookies in your browser, you will not be able to sign in or keep items in your cart.</p>
</section>

<section id="sharing">
    <h2>Sharing and disclosure</h2>
    <p>We share personal information only where it is needed and only to the extent necessary:</p>
    <ul>
        <li><strong>Store staff and administrators</strong> can see account, order and message details to run the shop.</li>
        <li><strong>Email delivery services</strong> process your name, email address and order summary when the store sends you a registration notice or order receipt.</li>
        <li><strong>The public</strong> can see the name, profile photo, rating, text and photos you choose to post in a review.</li>
        <li><strong>Authorities and advisers</strong> may receive information when the law requires it, to enforce our terms, or to protect the rights and safety of the store, our customers or others.</li>
    </ul>
    <p>We do not rent or sell customer lists to anyone.</p>
</section>

<section id="retention">
    <h2>Data retention</h2>
    <p>We keep your information for as long as your account is active and for as long as it is needed to keep order records, handle disputes and meet the store's record-keeping needs. Order records are kept as business records and are not removed automatically when you stop using the site. The website does not currently delete accounts automatically. An administrator can deactivate an account, which prevents it from signing in. Contact messages are kept until the store has dealt with them and no longer needs them.</p>
</section>

<section id="rights">
    <h2>Your rights and choices</h2>
    <p>Subject to applicable law, including the Philippine Data Privacy Act of 2012 (Republic Act No. 10173), you may ask to:</p>
    <ul>
        <li><strong>Access</strong> the personal information we hold about you.</li>
        <li><strong>Correct</strong> inaccurate or incomplete information. You can update your name, email address, contact number, address and photo yourself from your profile page.</li>
        <li><strong>Request deletion or restriction</strong> of your information, where it is no longer needed for orders or record-keeping.</li>
        <li><strong>Object</strong> to a particular use of your information or withdraw consent you have given.</li>
        <li><strong>Raise a complaint</strong> with us, and with the National Privacy Commission if you believe your data protection rights have been violated.</li>
    </ul>
    <p>To make a request, use the <a href="<?= e(url('contact.php')) ?>">contact form</a> or the email address below and tell us what you need. We may ask you to confirm your identity before acting on a request, and some information may need to be kept for order records.</p>
</section>

<section id="children">
    <h2>Children</h2>
    <p><?= e(SHOP_NAME) ?> is not directed at children. If you are a minor, please use the store with the knowledge and involvement of a parent or guardian. If you believe a child has given us personal information without appropriate permission, contact us so we can review it.</p>
</section>

<section id="changes">
    <h2>Changes to this policy</h2>
    <p>We may update this policy as the website changes. The "last updated" date at the top of the page shows when it was last revised. Continued use of the website after an update means you accept the revised policy.</p>
</section>

<section id="contact">
    <h2>Privacy contact</h2>
    <p>For privacy questions, access or correction requests, or concerns about how your information is handled, reach us here:</p>
    <div class="contact-card">
        <span>Email</span><strong><a href="mailto:<?= e(CONTACT_EMAIL) ?>"><?= e(CONTACT_EMAIL) ?></a></strong>
        <span>Online</span><strong><a href="<?= e(url('contact.php')) ?>">Contact form</a></strong>
        <span>Store</span><strong><?= e(CONTACT_ADDRESS) ?></strong>
    </div>
</section>
<?php
doc_close();
include('./includes/footer.php');
