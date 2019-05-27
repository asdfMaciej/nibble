
<?php foreach ($orders as $order): ?>
	<div class="order">
		<a href="/shop/order/{{order->id}}">
			#{{order->id}} {{order->date}}, zamówienie na {{order->brutto}} zł ({{order->netto}} zł netto)
		</a>
	</div>
<?php endforeach ?>
