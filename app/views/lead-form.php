<?php
/**
 * Formulário de contato / confirmação pós-envio.
 * Recebe $site (app/config/site.php) e $flash (flash_take('lead')).
 */
$formLabels = $site['form'] ?? [];
?>
<?php if (($flash['status'] ?? null) === 'ok'): ?>
    <div class="lead-success" data-lead-success>
        <h3>Você está na lista!</h3>
        <p>Em breve entraremos em contato. Se preferir, fale agora mesmo pelo WhatsApp.</p>
        <a class="btn btn-primary" href="<?= e(whatsapp_url($site, $flash['name'] ?? '')) ?>" target="_blank" rel="noopener">Falar no WhatsApp</a>
    </div>
<?php else: ?>
    <?php
    $errors = $flash['errors'] ?? [];
    $old = $flash['old'] ?? [];
    $oldInterests = is_array($old['interests'] ?? null) ? $old['interests'] : [];
    ?>
    <h2 class="lead-form__title"><?= e($site['waitlist']['card_title'] ?? 'Garanta sua vaga na lista') ?></h2>
    <p class="lead-form__subtitle"><?= e($site['waitlist']['card_subtitle'] ?? '') ?></p>

    <form id="lead-form" class="lead-form" data-lead-form action="api/lead.php" method="post" novalidate>
        <p class="form-status" data-form-status role="status" aria-live="polite"></p>

        <div class="form-grid">
            <div class="field">
                <label for="name"><?= e($formLabels['name_label'] ?? 'Nome') ?></label>
                <input type="text" id="name" name="name" autocomplete="name" required maxlength="120"
                    placeholder="<?= e($formLabels['name_placeholder'] ?? '') ?>"
                    value="<?= e($old['name'] ?? '') ?>"
                    <?= isset($errors['name']) ? 'aria-invalid="true" aria-describedby="err-name"' : '' ?>>
                <p class="field-error" id="err-name" data-error-for="name"><?= e($errors['name'] ?? '') ?></p>
            </div>

            <div class="field">
                <label for="email"><?= e($formLabels['email_label'] ?? 'Email') ?></label>
                <input type="email" id="email" name="email" autocomplete="email" required maxlength="190"
                    placeholder="<?= e($formLabels['email_placeholder'] ?? '') ?>"
                    value="<?= e($old['email'] ?? '') ?>"
                    <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="err-email"' : '' ?>>
                <p class="field-error" id="err-email" data-error-for="email"><?= e($errors['email'] ?? '') ?></p>
            </div>

            <div class="field">
                <label for="phone"><?= e($formLabels['phone_label'] ?? 'Telefone') ?></label>
                <input type="tel" id="phone" name="phone" inputmode="tel" autocomplete="tel" required
                    placeholder="<?= e($formLabels['phone_placeholder'] ?? '') ?>"
                    value="<?= e($old['phone'] ?? '') ?>"
                    <?= isset($errors['phone']) ? 'aria-invalid="true" aria-describedby="err-phone"' : '' ?>>
                <p class="field-error" id="err-phone" data-error-for="phone"><?= e($errors['phone'] ?? '') ?></p>
            </div>

            <div class="field">
                <label for="message"><?= e($formLabels['message_label'] ?? 'Mensagem') ?></label>
                <textarea id="message" name="message" required maxlength="2000" rows="3"
                    placeholder="<?= e($formLabels['message_placeholder'] ?? '') ?>"
                    <?= isset($errors['message']) ? 'aria-invalid="true" aria-describedby="err-message"' : '' ?>><?= e($old['message'] ?? '') ?></textarea>
                <p class="field-error" id="err-message" data-error-for="message"><?= e($errors['message'] ?? '') ?></p>
            </div>

            <?php if (!empty($site['products'])): ?>
            <fieldset class="field chip-group">
                <legend>Tenho interesse em</legend>
                <div class="chip-group__options">
                    <?php foreach ($site['products'] as $product): ?>
                        <label class="chip">
                            <input type="checkbox" name="interests[]" value="<?= e($product['id']) ?>"
                                <?= in_array($product['id'], $oldInterests, true) ? 'checked' : '' ?>>
                            <span><?= e($product['title']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="field-error" id="err-interests" data-error-for="interests"><?= e($errors['interests'] ?? '') ?></p>
            </fieldset>
            <?php endif; ?>

            <?php
            $policyUrl = $site['lgpd']['policy_url'] ?? '';
            $consentText = e($site['lgpd']['consent_text'] ?? '');
            $policyPhrase = 'Política de Privacidade';
            if (!empty($policyUrl) && str_contains($consentText, $policyPhrase)) {
                $consentText = str_replace(
                    $policyPhrase,
                    '<a href="' . e($policyUrl) . '">' . $policyPhrase . '</a>',
                    $consentText
                );
            }
            ?>
            <div class="field consent-field">
                <label>
                    <input type="checkbox" name="consent" value="1" required
                        <?= !empty($old['consent']) ? 'checked' : '' ?>
                        <?= isset($errors['consent']) ? 'aria-invalid="true" aria-describedby="err-consent"' : '' ?>>
                    <span><?= $consentText ?></span>
                </label>
                <p class="field-error" id="err-consent" data-error-for="consent"><?= e($errors['consent'] ?? '') ?></p>
            </div>

            <!-- Honeypot: campo invisível para humanos, nunca type="hidden" para não ser ignorado por bots simples. -->
            <div class="hp-field" aria-hidden="true">
                <label for="website">Deixe este campo em branco</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off" value="">
            </div>

            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="form_ts" value="<?= e(form_ts_issue()) ?>">

            <button type="submit" class="btn btn-primary lead-form__submit"><?= e($site['waitlist']['submit_label'] ?? 'Enviar') ?></button>
        </div>
    </form>
<?php endif; ?>
