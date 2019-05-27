<?php if (!$category->isEmpty()): ?>
	Obecna kategoria: {{category->name}}<br>
	Produkty:<br>
	<?php foreach ($products as $product): ?>
	<div>
		<a href="/shop/product/{{product->slug}}">{{product->name}}, {{product->price_normal_brutto}} zł</a>
	</div>
	<?php endforeach ?>
<?php endif ?>

<?php foreach ($child_categories as $category): ?>
<div>
	<b>Kategoria:</b> <a href="/shop/category/{{category->slug}}">{{category->name_pl}}</a>
</div>
<?php endforeach ?>