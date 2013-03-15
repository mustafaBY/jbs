
INSERT INTO `Clauses` (`AuthorID`, `EditorID`, `Partition`, `Title`, `IsProtected`, `IsXML`, `IsDOM`, `IsPublish`, `Text`) VALUES
(100, 100, 'CreateTicket/LOCK_OVERLIMITS', 'Аккаунт заблокирован за превышение использования CPU', 'no', 'yes', 'yes', 'yes', '<NOBODY>
<P>Уведомляем Вас о том, что ваш аккаунт %Login%, паркованный домен %Domain%, превысил использование процессорного времени, определённое вашим тарифом "%Scheme%". Поскольку превышения были систематические, и наши предыдущие уведомления вы проигнорировали - мы вынуждены заблокировать ваш аккаунт.
<BR /><BR />
Средняя нагрузка за последние %PeriodToLock% дней составила: %BUsage%%, при лимите тарифного плана: %QuotaCPU%%.
<BR /><BR />
Подробную статистику использования ресурсов, вы можете узнать в панели управления хостингом:<BR />
%Url%
</P>
</NOBODY>');


