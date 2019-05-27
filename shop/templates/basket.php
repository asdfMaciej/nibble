<b>Netto:</b> {{basket->netto}} zł<br>
<b>Brutto:</b> {{basket->brutto}} zł<br>
<b>Dostawa:</b> {{basket->shipment}} zł<br>
<b>Razem:</b> {{basket->total}} zł<br>
<h3>Produkty:</h3>

<?php foreach ($basket->products as $product): ?>
	<div>
		<b>{{product['name']}}</b><br>
		<b>Netto:</b>{{product['price_netto']}}<br>
		<b>Brutto:</b>{{product['price_brutto']}}<br>
		<b>Czas dodania:</b>{{product['added_time']}}
		<form action="/shop/basket" method="post">
			<input type="hidden" name="action" value="remove_item">
			<input type="hidden" name="slug" value="{{product['slug']}}">
			<input type="submit" value="Usuń z koszyka">
		</form>
	</div>
<?php endforeach ?>

<form action="/shop/order" method="post">
	<input type="hidden" name="action" value="new_order">
	<input type="submit" value="Złóż zamówienie">
</form>