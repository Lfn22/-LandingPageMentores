<?php
/**
 * Formulário de contato / confirmação pós-envio.
 * Recebe $site (app/config/site.php) e $flash (flash_take('lead')).
 */
?>
<?php if (($flash['status'] ?? null) === 'ok'): ?>
    <div class="lead-success" data-lead-success>
        <h3>Recebemos seu contato</h3>
        <p>Em breve entraremos em contato. Se preferir, fale agora mesmo pelo WhatsApp.</p>
        <a class="btn btn-whatsapp" href="<?= e(whatsapp_url($site, $flash['name'] ?? '')) ?>" target="_blank" rel="noopener">Falar no WhatsApp</a>
    </div>
<?php else: ?>
    <?php
    $errors = $flash['errors'] ?? [];
    $old = $flash['old'] ?? [];
    $oldInterests = is_array($old['interests'] ?? null) ? $old['interests'] : [];
    ?>
    <form id="lead-form" class="lead-form" data-lead-form action="api/lead.php" method="post" novalidate>
        <p class="form-status" data-form-status role="status" aria-live="polite"></p>

        <div class="form-grid">
            <div class="field">
                <label for="name">Nome</label>
                <input type="text" id="name" name="name" autocomplete="name" required maxlength="120"
                    value="<?= e($old['name'] ?? '') ?>"
                    <?= isset($errors['name']) ? 'aria-invalid="true" aria-describedby="err-name"' : '' ?>>
                <p class="field-error" id="err-name" data-error-for="name"><?= e($errors['name'] ?? '') ?></p>
            </div>

            <div class="field">
                <label for="phone">Telefone</label>
                <input type="tel" id="phone" name="phone" inputmode="tel" autocomplete="tel" required
                    placeholder="(11) 98765-4321"
                    value="<?= e($old['phone'] ?? '') ?>"
                    <?= isset($errors['phone']) ? 'aria-invalid="true" aria-describedby="err-phone"' : '' ?>>
                <p class="field-error" id="err-phone" data-error-for="phone"><?= e($errors['phone'] ?? '') ?></p>
            </div>

            <div class="field">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" autocomplete="email" required maxlength="190"
                    value="<?= e($old['email'] ?? '') ?>"
                    <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="err-email"' : '' ?>>
                <p class="field-error" id="err-email" data-error-for="email"><?= e($errors['email'] ?? '') ?></p>
            </div>

            <div class="field field--full">
                <label for="message">Mensagem</label>
                <textarea id="message" name="message" required maxlength="2000" rows="4"
                    <?= isset($errors['message']) ? 'aria-invalid="true" aria-describedby="err-message"' : '' ?>><?= e($old['message'] ?? '') ?></textarea>
                <p class="field-error" id="err-message" data-error-for="message"><?= e($errors['message'] ?? '') ?></p>
            </div>

            <?php if (!empty($site['products'])): ?>
            <fieldset class="field field--full chip-group">
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

            <div class="field field--full consent-field">
                <label>
                    <input type="checkbox" name="consent" value="1" required
                        <?= !empty($old['consent']) ? 'checked' : '' ?>
                        <?= isset($errors['consent']) ? 'aria-invalid="true" aria-describedby="err-consent"' : '' ?>>
                    <span><?= e($site['lgpd']['consent_text']) ?> <a href="<?= e($site['lgpd']['policy_url']) ?>">Política de Privacidade</a></span>
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

            <div class="field field--full">
                <button type="submit" class="btn btn-primary">Enviar contato</button>
            </div>
        </div>
    </form>
<?php endif; ?>
