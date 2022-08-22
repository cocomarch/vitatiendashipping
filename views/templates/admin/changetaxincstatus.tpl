

<a class="list-action-enable {if $value} action-enabled {else} action-disabled {/if}"
    href="index.php?tab=VitatiendaShippingCondition&id_condition={$tableRow.id_condition}&changeTaxIncVal&token={$token}">
	{if $value}<i class="icon-check"></i>{else}<i class="icon-remove"></i>{/if}
</a>