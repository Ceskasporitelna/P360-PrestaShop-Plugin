{**
* Module Platba360
*
* This source file is subject to the Open Software License v. 3.0 (OSL-3.0)
* that is bundled with this package in the file LICENSE.
* It is also available through the world-wide-web at this URL:
* https://opensource.org/licenses/OSL-3.0
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to application@brainweb.cz so we can send you a copy..
*
* @author    Ceska sporitelna, a.s. <developers@csas.cz>
* @copyright 2019 Ceska sporitelna, a.s.
* @license   Licensed under the Open Software License version 3.0  https://opensource.org/licenses/OSL-3.0
*
* Payment gateway operator and support: www.csas.cz
* Module development: www.csas.cz
*}

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
