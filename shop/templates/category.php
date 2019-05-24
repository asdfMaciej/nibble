<?php if (empty($category)): ?>
	Kategoria "{{category_slug}}" nie ma żadnych produktów. 
<?php else: ?>
	Nazwa kategorii: {{category->name}}<br>
	Produkty:<br>
	<?php foreach ($products as $product): ?>
	<div>
		<b>{{product->slug}}</b><br>
		{{product->name}}<br>
		<pre>{{product->desc}}</pre>
	</div>
	<?php endforeach ?>
<?php endif ?>