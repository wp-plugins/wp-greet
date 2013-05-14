<?php 
/*
 *  Template for the preview of a wp-greet card
 *  
 *  The following placeholders can be used:
 *  {%sendername%}					- name of the sender
 *  {%sendermail%}					- email address of the sender
 *  {%ccsender%}					- should the sender get a copy of the greet card mail?
 *  {%recvname%}					- name of the receiver
 *  {%recvmail%}					- email address of the receiver
 *  {%subject%}						- subject of the message
 *  {%wp-greet-default-header%}		- gives the wp-greet header (can be set in the admin dialog)
 *  {%wp-greet-default-footer%}		- gives the wp-greet footer (can be set in the admin dialog)
 *  {%image_url%}					- gives an img tag to show the greet card picture
 *  {%message%}						- the message
 *  {%send_time%}					- date/time when the card will be send
 */
?>
<table>
<tr>
   <th><?php  _e("From","wp-greet"); ?>:</th>
   <td>{%sendername%} &lt;{%sendermail%}&gt;{%ccsender%}</td>
</tr>

<tr>
   <th><?php _e("To","wp-greet"); ?>:</th>
   <td>{%recvname%}&nbsp;&lt;{%recvmail%}&gt;</td>
</tr>

<tr>
   <th><?php _e("Subject","wp-greet");?>:</th>
   <td>{%subject%}</td>
</tr>
</table>

<div>{%wp-greet-default-header%}</div>

{%image_url%}
 
<p>{%message%}</p>

{%wp-greet-default-footer%}

<?php if ($wpg_options['wp-greet-future-send'] and $_POST['fsend']!=""): ?>
<p><strong><?php _e("This card will be sent at","wp-greet"); ?> {%send_time%}</strong></p>
<?php endif;?>