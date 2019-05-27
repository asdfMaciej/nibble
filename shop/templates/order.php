<b>{{order->date}}</b>
<u>#{{order->id}}</u> <br>
Zapłacono razem z wysyłką: {{order->total}} zł<br>
Wartość netto: {{order->netto}}<br>
Wartość brutto: {{order->brutto}}<br>
Adres: {{order->address}}<br>
IP: {{order->ip}}

<h3>Produkty:</h3>

<?php foreach ($products as $product): ?>
<div>

	{{product->quantity}}x - <a href="/shop/product/{{product->slug}}">{{product->name}}</a>, {{product->priceg}} zł ({{product->vat}}%)
</div>
<?php endforeach ?>