## Описание к Bitrix: управление сайтом

**Предполагается, что в папку 'www' уже установлен сайт Bitrix24 из другого репозитория - https://github.com/ArtemaMois/Bitrix24TestTask**

Прописываем локально адрес **intershop.ru** в "C:\Windows\System32\drivers\etc\hosts":

![image](https://github.com/user-attachments/assets/215f2339-838a-4625-93ad-e84bb3d8637f)




Создаем новый сайт с доменным именем **intershop.ru** через меню в консоли.

1. Выбираем 8 пункт для создания нового сайта:

![image](https://github.com/user-attachments/assets/ffbfb473-e8e6-479b-a946-efd9f012cbb9)

2. Выбираем 1 пункт:

![image](https://github.com/user-attachments/assets/11908d9a-fd54-4c85-abef-f6c07248dd6b)

3. Вводим доменное имя **intershop.ru**:  

![image](https://github.com/user-attachments/assets/1488d7f0-add2-4492-a890-cb13b410e817)

4. Выбираем отдельное ядро для сайта:

![image](https://github.com/user-attachments/assets/490a724e-4d3b-4f36-8bd4-931b89e474b5)

Новый сайт установлен.

Поставленная задача: 
Добрый день,
Необходимо развернуть 1С-Битрикс в редакции с интернет-магазином и Битрикс 24 на локале и сделать следующее,
при оформлении заказа на сайте 1С-Битрикс, в Битрикс 24 формируется сделка и все данные из заказа
отображаются в полях сделки, причем если мы их отредактируем то эти изменения так же отразятся и на 1С-Битрикс, интеграция должна быть в двух направлениях.

**Описание выполненной работы:** 
1. После создания заказа в Bitrix: управление сайтом создается сделка в BX24, а также счет к этой сделке, в котором указана сумма за товары.
2. После изменения статуса сделки в BX24, статус меняется и в Bitrix: управление сайтом, а также, если изменить статус заказа в адмнике Bitrix: управление сайтом, то изменения отобразятся и в Bitrix24. 
3. После добавления/удаления или изменения количества товаров в BX24 изменения отображаются в Bitrix: управление сайтом.
4. Если товара нет в BX24, то при создании заказа, все товары из него будут созданы в BX24.
5. При создании заказа в Bitrix: Упралвение сайтом синхронизируется количество товаров на складе в BX24.
6. При отмене заказа на любом из сайтов, изменения отобразятся и на другом. 
