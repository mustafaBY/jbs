{*
 *  Joonte Billing System
 *  Copyright © 2020 Alex Keda, for www.host-food.ru
 *}
{assign var=Theme value="Оканчивается срок блокировки заказа на вторичный DNS [{$DNSmanagerOrder.Login|default:'$DNSmanagerOrder.Login'}]" scope=global}
{assign var=ExpDate value=$StatusDate + $Config.Tasks.Types.DNSmanagerForDelete.DNSmanagerDeleteTimeout * 24 * 3600}
Здравствуйте, {$User.Name|default:'$User.Name'}!

Уведомляем Вас о том, что оканчивается срок блокировки Вашего заказа №{$DNSmanagerOrder.OrderID|string_format:"%05u"} на вторичный DNS, логин {$DNSmanagerOrder.Login|default:'$DNSmanagerOrder.Login'}, домен {$DNSmanagerOrder.Domain|default:'$DNSmanagerOrder.Domain'}.
Дата удаления заказа {$ExpDate|date_format:"%d.%m.%Y"}

{if !$MethodSettings.CutSign}
--
{$From.Sign|default:'$From.Sign'}

{/if}


