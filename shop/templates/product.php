<h2>Produkt {{product_slug}}:</h2>

<?php if (!$product->isEmpty()): ?>
	<div class="product">
		ID: <b>{{product->id}}</b><br>
		Nazwa: <b>{{product->name}}</b><br>
		Opis:
		<pre>{{product->desc}}</pre>

		VAT: {{product->vat}}%<br>
		Cena netto: {{product->price_normal_netto}}<br>
		Cena brutto: {{product->price_normal_brutto}}<br>
		Cena promocyjna netto: {{product->price_discounted_netto}}<br>
		Cena promocyjna brutto: {{product->price_discounted_brutto}}<br>
		Zdjęcie: {{product->photo_small}}<br>

		<form action="./{{product_slug}}" method="post">
			<input type="hidden" name="action" value="add_basket">
			<input type="submit" value="Dodaj do koszyka">
		</form>
	</div>
<?php else: ?>
	<b>Nie istnieje.</b>
<?php endif ?>