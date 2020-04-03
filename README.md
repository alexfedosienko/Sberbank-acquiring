# Sberbank acquiring
Библиотека для работы с экваирингом от Сбербанка

### Возможности
 * Генерация URL для платежа
 * Полный и частичный возврат средств
 * Проверка платежа
 * Тестовый и боевой шлюзы Сбербанка

### Установка
С помощью [composer](https://getcomposer.org/):
```bash
composer require alexfedosienko/Sberbank-acquiring
```
Подключение:
```php
use AFedosienko\Sberbank;
```
## Примеры использования
### 1. Инициализация класса
```php
$sberbank = new SBRF_API('username', 'password', 'https://domain.com/payment/success/', 'https://domain.com/payment/fail/', false);
```

### 2. Получаем URL для оплаты
```php
$result = $sberbank->registerOrder(
	1000, // сумма заказа в рублях
	'test_123', // номер заказа в система
	'Описание' // описание заказа
);
if ($result) header('location: '.$result['formUrl']);
```
Если заказ уже был создан и не оплачен то вернется ссылка на первую оплату, в ином случае будет создан новый заказ с номером test_123_dmyHis

### 3. Получаем статус платежа
```php
/*
$orderId - уникальный идентификатор в платежной системе сбербанка, его получаем при регистрации заказа
$orderNumber - номер заказа в нашей системе
можно указать что-то одно, если указать оба аргумента, приоритет будет у $orderId
*/
$result = $sberbank->getOrderInfo($orderId, $orderNumber);
```

### 4. Делаем полную отмену платежа
```php
/*
$orderId - уникальный идентификатор в платежной системе сбербанка, его получаем при регистрации заказа
*/
$result = $sberbank->reverseOrderSum($orderId);
```
### 5. Делаем частичный возврат
```php
/*
$orderId - уникальный идентификатор в платежной системе сбербанка, его получаем при регистрации заказа
$amount - сумма в рублях
*/
$result = $sberbank->refudOrderSum($orderId, $amount);
```