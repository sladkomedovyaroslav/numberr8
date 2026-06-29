/**
 * main.js - AJAX-отправка формы, валидация, мобильное меню
 * Ресторан «Вкус Востока»
 */

document.addEventListener('DOMContentLoaded', () => {
    'use strict';
    
    document.documentElement.classList.add('js-enabled');
    
    initMobileMenu();
    initSmoothScroll();
    initBookingForm();
});

/**
 * Мобильное меню
 */
function initMobileMenu() {
    const toggle = document.querySelector('.mobile-toggle');
    const menu = document.querySelector('.nav-menu');
    
    if (!toggle || !menu) return;
    
    toggle.addEventListener('click', () => {
        menu.classList.toggle('active');
        toggle.classList.toggle('active');
    });
    
    menu.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            menu.classList.remove('active');
            toggle.classList.remove('active');
        });
    });
}

/**
 * Плавная прокрутка к якорям
 */
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#' || href === '') return;
            
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            }
        });
    });
}

/**
 * AJAX-отправка формы бронирования
 */
function initBookingForm() {
    const form = document.getElementById('booking-form');
    if (!form) return;
    
    const submitBtn = document.getElementById('submit-btn');
    const responseDiv = document.getElementById('form-response');
    const errorSpans = form.querySelectorAll('.error-message');
    
    // Очистка всех ошибок
    function clearErrors() {
        errorSpans.forEach(span => {
            span.textContent = '';
        });
        form.querySelectorAll('.error').forEach(el => {
            el.classList.remove('error');
        });
    }
    
    // Отображение ошибок валидации
    function showErrors(errors) {
        clearErrors();
        for (const [field, message] of Object.entries(errors)) {
            const errorSpan = form.querySelector(`[data-error="${field}"]`);
            if (errorSpan) {
                errorSpan.textContent = message;
            }
            
            // Подсвечиваем поле с ошибкой
            let input;
            if (field === 'dishes') {
                input = form.querySelector('#dishes');
            } else if (field === 'gender') {
                input = form.querySelector('input[name="gender"]');
            } else {
                input = form.querySelector(`[name="${field}"]`);
            }
            
            if (input) {
                input.classList.add('error');
            }
        }
    }
    
    // Отображение ответа от сервера
    function showResponse(message, type = 'success') {
        if (!responseDiv) return;
        
        responseDiv.innerHTML = message;
        responseDiv.className = `form-response ${type}`;
        responseDiv.style.display = 'block';
        
        // Прокручиваем к сообщению
        responseDiv.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'center' 
        });
    }
    
    // Экранирование HTML
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    
    // Клиентская валидация (до отправки на сервер)
    function clientValidate() {
        const errors = {};
        
        const fullName = form.querySelector('#full_name')?.value.trim() || '';
        const phone = form.querySelector('#phone')?.value.trim() || '';
        const email = form.querySelector('#email')?.value.trim() || '';
        const birthDate = form.querySelector('#birth_date')?.value || '';
        const gender = form.querySelector('input[name="gender"]:checked')?.value || '';
        const dishes = form.querySelector('#dishes')?.selectedOptions || [];
        const agreed = form.querySelector('input[name="agreed"]:checked')?.value || '';
        
        // Валидация ФИО
        if (!fullName) {
            errors.full_name = 'ФИО обязательно для заполнения';
        } else if (fullName.length > 150) {
            errors.full_name = 'ФИО не должно превышать 150 символов';
        } else if (!/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u.test(fullName)) {
            errors.full_name = 'ФИО должно содержать только буквы, пробелы и дефисы';
        }
        
        // Валидация телефона
        if (!phone) {
            errors.phone = 'Телефон обязателен для заполнения';
        } else if (!/^[\d\s\+\(\)-]{5,20}$/.test(phone)) {
            errors.phone = 'Телефон должен содержать только цифры, пробелы, +, (, ), - (5-20 символов)';
        }
        
        // Валидация email
        if (!email) {
            errors.email = 'Email обязателен для заполнения';
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            errors.email = 'Введите корректный email (пример: name@domain.ru)';
        }
        
        // Валидация даты рождения
        if (!birthDate) {
            errors.birth_date = 'Дата рождения обязательна для заполнения';
        } else if (new Date(birthDate) > new Date()) {
            errors.birth_date = 'Дата рождения не может быть в будущем';
        }
        
        // Валидация пола
        if (!gender) {
            errors.gender = 'Выберите пол';
        }
        
        // Валидация выбранных блюд
        if (dishes.length === 0) {
            errors.dishes = 'Выберите хотя бы одно блюдо';
        }
        
        // Валидация согласия
        if (!agreed) {
            errors.agreed = 'Вы должны согласиться с условиями бронирования';
        }
        
        return errors;
    }
    
    // Основной обработчик отправки формы
    form.addEventListener('submit', async function(e) {
        // Если JavaScript включен - предотвращаем обычную отправку
        e.preventDefault();
        
        // Очищаем предыдущие ошибки и ответ
        clearErrors();
        if (responseDiv) {
            responseDiv.style.display = 'none';
        }
        
        // Клиентская валидация
        const clientErrors = clientValidate();
        if (Object.keys(clientErrors).length > 0) {
            showErrors(clientErrors);
            // Прокручиваем к первой ошибке
            const firstError = form.querySelector('.error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }
        
        // Меняем состояние кнопки
        const originalHTML = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';
        submitBtn.disabled = true;
        
        // Собираем данные формы
        const formData = new FormData(form);
        const data = {};
        
        for (const [key, value] of formData.entries()) {
            if (key === 'dishes[]') {
                if (!data.dishes) {
                    data.dishes = [];
                }
                data.dishes.push(value);
            } else if (key !== 'csrf_token') {
                data[key] = value;
            }
        }
        
        // Определяем метод и URL
        const isLoggedIn = document.body.dataset.loggedIn === 'true';
        const userId = document.body.dataset.userId;
        
        let url = 'api.php';
        let method = 'POST';
        
        if (isLoggedIn && userId) {
            url = `api.php?id=${userId}`;
            method = 'PUT';
        }
        
        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (response.ok) {
                if (result.login && result.password) {
                    // Новое бронирование - показываем логин и пароль
                    showResponse(
                        `<h4><i class="fas fa-check-circle"></i> Бронирование подтверждено!</h4>
                         <p><strong>🔑 Логин:</strong> <code>${escapeHtml(result.login)}</code></p>
                         <p><strong>🔒 Пароль:</strong> <code>${escapeHtml(result.password)}</code></p>
                         <p class="warning"><i class="fas fa-exclamation-triangle"></i> Сохраните эти данные! Они понадобятся для изменения брони.</p>
                         <p><a href="login.php" class="btn btn-sm btn-primary">Войти для редактирования</a></p>`,
                        'success'
                    );
                    // Очищаем форму после успешной отправки
                    form.reset();
                } else {
                    showResponse(
                        '<h4><i class="fas fa-check-circle"></i> Данные успешно обновлены!</h4>',
                        'success'
                    );
                }
            } else if (response.status === 400 && result.errors) {
                // Ошибки валидации с сервера
                showErrors(result.errors);
                showResponse(
                    '<i class="fas fa-exclamation-circle"></i> Пожалуйста, исправьте ошибки в форме',
                    'error'
                );
            } else if (response.status === 401) {
                showResponse(
                    '<i class="fas fa-lock"></i> Необходима авторизация. <a href="login.php">Войти</a>',
                    'error'
                );
            } else if (response.status === 404) {
                showResponse(
                    '<i class="fas fa-info-circle"></i> Данные не найдены. Заполните форму заново.',
                    'error'
                );
            } else {
                showResponse(
                    `<i class="fas fa-times-circle"></i> Ошибка: ${escapeHtml(result.error || 'Неизвестная ошибка сервера')}`,
                    'error'
                );
            }
        } catch (error) {
            showResponse(
                `<i class="fas fa-times-circle"></i> Ошибка сети: ${escapeHtml(error.message)}<br>
                 <small>Проверьте подключение к интернету и попробуйте снова</small>`,
                'error'
            );
        } finally {
            // Восстанавливаем кнопку
            submitBtn.innerHTML = originalHTML;
            submitBtn.disabled = false;
        }
    });
    
    // Загрузка данных пользователя при авторизации
    async function loadUserData() {
        const isLoggedIn = document.body.dataset.loggedIn === 'true';
        const userId = document.body.dataset.userId;
        
        if (!isLoggedIn || !userId) return;
        
        try {
            const response = await fetch(`api.php?id=${userId}`);
            
            if (response.ok) {
                const data = await response.json();
                
                // Заполняем поля формы
                if (data.full_name) {
                    form.querySelector('#full_name').value = data.full_name;
                }
                if (data.phone) {
                    form.querySelector('#phone').value = data.phone;
                }
                if (data.email) {
                    form.querySelector('#email').value = data.email;
                }
                if (data.birth_date) {
                    form.querySelector('#birth_date').value = data.birth_date;
                }
                if (data.gender) {
                    const radio = form.querySelector(`input[name="gender"][value="${data.gender}"]`);
                    if (radio) radio.checked = true;
                }
                if (data.dishes && Array.isArray(data.dishes)) {
                    const select = form.querySelector('#dishes');
                    if (select) {
                        for (const option of select.options) {
                            option.selected = data.dishes.includes(option.value);
                        }
                    }
                }
                if (data.biography) {
                    form.querySelector('#biography').value = data.biography;
                }
                if (data.agreed) {
                    const checkbox = form.querySelector('input[name="agreed"]');
                    if (checkbox) checkbox.checked = true;
                }
                
                // Меняем текст кнопки для авторизованных пользователей
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Обновить бронирование';
                }
            } else if (response.status === 404) {
                console.log('Данные не найдены, форма будет заполнена заново');
            }
        } catch (error) {
            console.error('Ошибка загрузки данных пользователя:', error);
        }
    }
    
    // Загружаем данные при инициализации
    loadUserData();
}