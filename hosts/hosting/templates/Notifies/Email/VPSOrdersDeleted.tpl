{*
 *  Joonte Billing System
 *  Copyright © 2012 Vitaly Velikodnyy
 *}
{assign var=Theme value="Заказ виртуального сервера #{$OrderID|string_format:"%05u"}/[{$Login|default:'$Login'}] удален" scope=global}
Здравствуйте, {$User.Name|default:'$User.Name'}!

Уведомляем Вас о том, что {$StatusDate|date_format:"%d.%m.%Y"} Ваш заказ на виртуальный выделенный сервер (VPS) №{$OrderID|string_format:"%05u"} был удален.
IP адрес: {$Login|default:'$Login'}

{$From.Sign|default:'$From.Sign'}
