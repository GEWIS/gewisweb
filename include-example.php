<?php require_once '../include.php'; ?>

<?php
// Set a title
$this->headTitle('My awesome page - GEWIS');

// Get information about the current user
$user = $this->identity();

$userLoggedIn = false;
if ($user instanceof \User\Model\User) {
    $userLoggedIn = true;
}

$member = $userLoggedIn ? $user->getMember() : null;

// Get the current language
$lang = $this->plugin('translate')->getTranslator()->getLocale();
?>

<div class="container">
    <h1>I am a test page.</h1>
    <hr>
    <?php if ($userLoggedIn): ?>
        Currently <strong><?= $member->getFullname() ?></strong> is logged in.
        <?= $member->getGender() === 'f' ? 'She' : 'He' ?> is a member of
        <?= count($member->getCurrentOrganInstallations()) ?> organs:
        <ul>
            <?php foreach ($member->getCurrentOrganInstallations() as $organInstallation): ?>
                <li><?= $organInstallation->getOrgan()->getName() ?></li>
            <?php endforeach; ?>
        </ul>
        <?php var_dump($member->toArray()) ?>
    <?php else: ?>
        Log in to see more.
    <?php endif; ?>
</div>
