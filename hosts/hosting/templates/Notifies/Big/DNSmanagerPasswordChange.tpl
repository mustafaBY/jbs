{*
 *  Joonte Billing System
 *  Copyright © 2020 Alex Keda, for www.host-food.ru
 *}
{assign var=Theme value="Пароль для заказа вторичного DNS [{$Login|default:'$Login'}] изменен" scope=global}

Уведомляем Вас о том, что {$smarty.now|date_format:"%d.%m.%Y"} пароль на Ваш заказ вторичного DNS №{$OrderID|string_format:"%05u"} был изменен.

Ваши новые данные для доступа к аккаунту на сервере:
  * Адрес панели управления:
      {$Server.Params.Url|default:'$Server.Params.Url'}
  * Логин:
      {$Login|default:'$Login'}
  * Пароль:
      {$Password|default:'$Password'}

Сохраните эти данные в надежном месте, они потребуются для дальнейшей работы.

