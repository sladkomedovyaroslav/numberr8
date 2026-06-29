<form id="booking-form" action="/restaurant/" method="POST" novalidate>
    <?php if (empty($_SESSION['csrf_token'])): ?>
        <?php $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); ?>
    <?php endif; ?>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    
    <div class="form-grid">
        <!-- ФИО -->
        <div class="form-group">
            <label for="full_name">ФИО *</label>
            <input type="text" id="full_name" name="full_name" 
                   value="<?= htmlspecialchars($formData['full_name'] ?? '') ?>"
                   placeholder="Иванов Иван Иванович" required maxlength="150">
            <span class="error-message" data-error="full_name"></span>
        </div>
        
        <!-- Телефон -->
        <div class="form-group">
            <label for="phone">Телефон *</label>
            <input type="tel" id="phone" name="phone"
                   value="<?= htmlspecialchars($formData['phone'] ?? '') ?>"
                   placeholder="+7 (999) 123-45-67" required>
            <span class="error-message" data-error="phone"></span>
        </div>
        
        <!-- Email -->
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($formData['email'] ?? '') ?>"
                   placeholder="example@mail.ru" required>
            <span class="error-message" data-error="email"></span>
        </div>
        
        <!-- Дата рождения -->
        <div class="form-group">
            <label for="birth_date">Дата рождения *</label>
            <input type="date" id="birth_date" name="birth_date"
                   value="<?= htmlspecialchars($formData['birth_date'] ?? '') ?>"
                   max="<?= date('Y-m-d') ?>" required>
            <span class="error-message" data-error="birth_date"></span>
        </div>
        
        <!-- Пол -->
        <div class="form-group">
            <label>Пол *</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="gender" value="male" 
                           <?= ($formData['gender'] ?? '') === 'male' ? 'checked' : '' ?> required>
                    Мужской
                </label>
                <label class="radio-label">
                    <input type="radio" name="gender" value="female"
                           <?= ($formData['gender'] ?? '') === 'female' ? 'checked' : '' ?>>
                    Женский
                </label>
            </div>
            <span class="error-message" data-error="gender"></span>
        </div>
        
        <!-- Любимые языки -->
        <div class="form-group full-width">
            <label for="languages">Любимые языки программирования *</label>
            <select id="languages" name="languages[]" multiple required size="6">
                <?php 
                $languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
                foreach ($languages as $lang):
                    $selected = in_array($lang, $formData['languages'] ?? []) ? 'selected' : '';
                ?>
                    <option value="<?= $lang ?>" <?= $selected ?>><?= $lang ?></option>
                <?php endforeach; ?>
            </select>
            <small>Удерживайте Ctrl/Cmd для выбора нескольких</small>
            <span class="error-message" data-error="languages"></span>
        </div>
        
        <!-- Биография -->
        <div class="form-group">
            <label for="biography">О себе</label>
            <textarea id="biography" name="biography" rows="4"
                      placeholder="Расскажите о вашем опыте..."><?= htmlspecialchars($formData['biography'] ?? '') ?></textarea>
        </div>
        
        <!-- Согласие -->
        <div class="form-group full-width">
            <label class="checkbox-label">
                <input type="checkbox" name="agreed" value="1"
                       <?= !empty($formData['agreed']) ? 'checked' : '' ?> required>
                Я согласен с <a href="#" target="_blank">условиями бронирования</a> *
            </label>
            <span class="error-message" data-error="agreed"></span>
        </div>
    </div>
    
    <button type="submit" class="btn btn-primary btn-lg" id="submit-btn">
        <i class="fas fa-calendar-check"></i> Забронировать столик
    </button>
    
    <div id="form-response" class="form-response" style="display:none;"></div>
</form>