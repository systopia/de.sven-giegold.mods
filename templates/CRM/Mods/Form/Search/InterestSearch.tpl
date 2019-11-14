{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{* Default template custom searches. This template is used automatically if templateFile() function not defined in
   custom search .php file. If you want a different layout, clone and customize this file and point to new file using
   templateFile() function.*}
<div class="crm-block crm-form-block crm-contact-custom-search-form-block">
  <div class="crm-accordion-wrapper crm-custom_search_form-accordion {if $rows}collapsed{/if}">
    <div class="crm-accordion-header crm-master-accordion-header">
        {ts}Edit Search Criteria{/ts}
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
      <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>

      <fieldset>
        <legend>Selektion</legend>
        <p class="description">Wählen Sie für jedes der folgenden Kriterien,
          welche Kontakte zum Suchergebnis hinzugefügt werden sollen. Für jedes
          Feld können Einschlusskriterien (mindestens einer der Werte muss
          übereinstimmen) und Ausschlusskriterien (keiner der Werte darf
          übereinstimmen) ausgewählt werden.</p>
        <p class="description">Die Kombination von Ein- und
          Ausschlusskriterien bildet ein neues Selektionssegment, das dem
          Suchergebnis hinzugefügt wird. Jedes Selektionssegment fügt also einen
          weiteren Satz von Kontakten dem Suchergebnis hinzu
          (ODER-Verknüpfung). Um Werte aus dem gesamten Suchergebnis
          auszuschließen, verwenden Sie das jeweilige Feld im Abschnitt
          "Filter".</p>

          {capture assign="first_selection_field"}1{/capture}
          {foreach from=$selection key=field_name item=field}
            <table class="form-layout">
                {if not $first_selection_field}
                  <caption style="text-align: left; font-weight: bold;">zusätzlich Kontakte mit folgenden Kriterien:</caption>
                {/if}
              <tr class="crm-contact-custom-search-form-row-{$field_name}">
                  {foreach from=$field item=element}
                    <td class="label">{$form.$element.label}</td>
                    <td>{$form.$element.html}</td>
                  {/foreach}
              </tr>
                {capture assign="first_selection_field"}0{/capture}
            </table>
          {/foreach}

      </fieldset>

      <fieldset>
        <legend>Filter</legend>
        <p class="description">Das im Abschnitt "Selektion" kombinierte
          Suchergebnis kann mit den folgenden Feldern gefiltert werden.
          "Einschließen"-Felder behalten nur Kontakte, bei denen mindestens
          einer der ausgewählten Werte übereinstimmt. "Ausschließen"-Felder
          entfernen alle Kontakte, bei denen mindestens einer der ausgewählten
          Werte übereinstimmt. Die Selektionsfelder sind hier erneut zum
          Ausschließen verfügbar.</p>
        <table class="form-layout">
            {foreach from=$filters item=element}
              <tr class="crm-contact-custom-search-form-row-{$element}">
                <td class="label">{$form.$element.label}</td>
                <td>{$form.$element.html}</td>
              </tr>
            {/foreach}
        </table>
      </fieldset>

      <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
    </div><!-- /.crm-accordion-body -->
  </div><!-- /.crm-accordion-wrapper -->
</div><!-- /.crm-form-block -->

{if $rowsEmpty || $rows}
  <div class="crm-content-block">
      {if $rowsEmpty}
          {include file="CRM/Contact/Form/Search/Custom/EmptyResults.tpl"}
      {/if}

      {if $summary}
          {$summary.summary}: {$summary.total}
      {/if}

      {if $rows}
        <div class="crm-results-block">
            {* Search request has returned 1 or more matching rows. Display results and collapse the search criteria fieldset. *}
            {* This section handles form elements for action task select and submit *}
          <div class="crm-search-tasks">
              {include file="CRM/Contact/Form/Search/ResultTasks.tpl"}
          </div>
            {* This section displays the rows along and includes the paging controls *}
          <div class="crm-search-results">

              {include file="CRM/common/pager.tpl" location="top"}

              {* Include alpha pager if defined. *}
              {if $atoZ}
                  {include file="CRM/common/pagerAToZ.tpl"}
              {/if}

              {strip}
                <table class="selector row-highlight" summary="{ts}Search results listings.{/ts}">
                  <thead class="sticky">
                  <tr>
                    <th scope="col" title="Select All Rows">{$form.toggleSelect.html}</th>
                      {foreach from=$columnHeaders item=header}
                        <th scope="col">
                            {if $header.sort}
                                {assign var='key' value=$header.sort}
                                {$sort->_response.$key.link}
                            {else}
                                {$header.name}
                            {/if}
                        </th>
                      {/foreach}
                    <th>&nbsp;</th>
                  </tr>
                  </thead>

                    {counter start=0 skip=1 print=false}
                    {foreach from=$rows item=row}
                      <tr id='rowid{$row.contact_id}' class="{cycle values="odd-row,even-row"}">
                          {assign var=cbName value=$row.checkbox}
                        <td>{$form.$cbName.html}</td>
                          {foreach from=$columnHeaders item=header}
                              {assign var=fName value=$header.sort}
                              {if $fName eq 'sort_name'}
                                <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`&key=`$qfKey`&context=custom"}">{$row.sort_name}</a></td>
                              {else}
                                <td>{$row.$fName}</td>
                              {/if}
                          {/foreach}
                        <td>{$row.action}</td>
                      </tr>
                    {/foreach}
                </table>
              {/strip}

              {include file="CRM/common/pager.tpl" location="bottom"}

            </p>
              {* END Actions/Results section *}
          </div>
        </div>
      {/if}



  </div>
{/if}
