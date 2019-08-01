{if $status == 'ok'}
	<p>{l s='Vaše objednávka č. %s je kompletní. Platba byla úspěšně zadána.' sprintf=[$id_order] d='Modules.Platba360.Shop'}
		<br /><br />
		<strong>{l s='Vaše objednávka bude odeslána, jakmile obdržíme vaši platbu.' d='Modules.Platba360.Shop'}</strong>
		<br /><br />
		{l s='V případě jakýchkoli dotazů nebo dalších informací prosím kontaktujte naše' d='Modules.Platba360.Shop'}
		<a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='zákaznické oddělení.' d='Modules.Platba360.Shop'}</a>.
	</p>
{else}
	<p class="warning">
		<strong>
			{l s='Všimli jsme si, že se vyskytl problém s vaší objednávkou.' d='Modules.Platba360.Shop'}
		</strong>
		<br /><br />
		{l s='Pokud si myslíte, že se jedná o chybu, můžete se obrátit na naše' d='Modules.Platba360.Shop'}
		
		<a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='zákaznické oddělení' d='Modules.Platba360.Shop'}</a>.
	</p>
{/if}
