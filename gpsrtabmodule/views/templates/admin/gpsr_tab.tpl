<form action="{url to save}" method="post">
    <label for="manufacturer">Wybierz producenta:</label>
    <select name="manufacturer" id="manufacturer">
        {foreach from=$manufacturers item=manufacturer}
            <option value="{$manufacturer.id_manufacturer}" {if $gpsrContent.id_manufacturer == $manufacturer.id_manufacturer}selected{/if}>{$manufacturer.name}</option>
        {/foreach}
    </select>
    <br><br>
    <label for="gpsrContent">Treść HTML:</label>
    <textarea name="gpsrContent" id="gpsrContent" rows="10" cols="50">{$gpsrContent.content}</textarea>
    <br><br>
    <label for="gpsrEnabled">Włącz/wyłącz zakładkę GPSR:</label>
    <input type="checkbox" name="gpsrEnabled" id="gpsrEnabled" value="1" {if $gpsrContent.enabled}checked{/if}>
    <br><br>
    <input type="submit" name="save" value="Zapisz">
    <input type="submit" name="save_and_add" value="Zapisz i dodaj kolejny">
</form>