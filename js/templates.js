(function() {
  var template = Handlebars.template, templates = OCA.LdapOrgTempaltes = OCA.LdapOrgTempaltes || {};
templates['main_add_group'] = template({"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<form id=\"add-group-form\">\n	<label for=\"add-group-name\">"
    + alias4(((helper = (helper = helpers.nameTXT || (depth0 != null ? depth0.nameTXT : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"nameTXT","hash":{},"data":data}) : helper)))
    + "</label> <input type=\"text\" name=\"group_name\" id=\"add-group-name\" placeholder=\""
    + alias4(((helper = (helper = helpers.nameTXT || (depth0 != null ? depth0.nameTXT : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"nameTXT","hash":{},"data":data}) : helper)))
    + "\">\n	<button id=\"add-group-create\" type=\"submit\">"
    + alias4(((helper = (helper = helpers.addTXT || (depth0 != null ? depth0.addTXT : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"addTXT","hash":{},"data":data}) : helper)))
    + "</button>\n	<div><span class=\"msg\"></span></div>\n</form>\n";
},"useData":true});
templates['main_content'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=container.escapeExpression, alias2=depth0 != null ? depth0 : (container.nullContext || {});

  return "	<h2>"
    + alias1(container.lambda(((stack1 = (depth0 != null ? depth0.group : depth0)) != null ? stack1.ldapcontacts_name : stack1), depth0))
    + "</h2>\n\n	<div class=\"content-nav\">\n"
    + ((stack1 = helpers["if"].call(alias2,((stack1 = (depth0 != null ? depth0.group : depth0)) != null ? stack1.ldaporg_canedit : stack1),{"name":"if","hash":{},"fn":container.program(2, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + ((stack1 = helpers["if"].call(alias2,(depth0 != null ? depth0.me : depth0),{"name":"if","hash":{},"fn":container.program(4, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "	</div>\n\n"
    + ((stack1 = helpers["if"].call(alias2,(depth0 != null ? depth0.exportURL : depth0),{"name":"if","hash":{},"fn":container.program(7, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\n	<br><div class=\"msg-container\"><span class=\"msg\"></span></div><br>\n\n\n	<h3>"
    + alias1(((helper = (helper = helpers.membersTXT || (depth0 != null ? depth0.membersTXT : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(alias2,{"name":"membersTXT","hash":{},"data":data}) : helper)))
    + ((stack1 = helpers["if"].call(alias2,(depth0 != null ? depth0.memberCount : depth0),{"name":"if","hash":{},"fn":container.program(9, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "</h3>\n"
    + ((stack1 = helpers["if"].call(alias2,((stack1 = (depth0 != null ? depth0.group : depth0)) != null ? stack1.ldaporg_members : stack1),{"name":"if","hash":{},"fn":container.program(11, data, 0),"inverse":container.program(24, data, 0),"data":data})) != null ? stack1 : "");
},"2":function(container,depth0,helpers,partials,data) {
    var helper;

  return "			<span class=\"search\"><input type=\"search\" id=\"group_add_member\" placeholder=\""
    + container.escapeExpression(((helper = (helper = helpers.addMemberTXT || (depth0 != null ? depth0.addMemberTXT : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"addMemberTXT","hash":{},"data":data}) : helper)))
    + "\"><span class=\"abort\"></span></span>\n			<div class=\"search-suggestions\"></div>\n";
},"4":function(container,depth0,helpers,partials,data) {
    var stack1;

  return ((stack1 = helpers["if"].call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.notForcedMembership : depth0),{"name":"if","hash":{},"fn":container.program(5, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "");
},"5":function(container,depth0,helpers,partials,data) {
    var helper;

  return "				<a href=\"#\" id=\"leave_group\" class=\"leave\">"
    + container.escapeExpression(((helper = (helper = helpers.endGroupMembershipTXT || (depth0 != null ? depth0.endGroupMembershipTXT : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"endGroupMembershipTXT","hash":{},"data":data}) : helper)))
    + "</a>\n";
},"7":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "		<div id=\"export_member_details\"><a class=\"button\" target=\"_blank\" href=\""
    + alias4(((helper = (helper = helpers.exportURL || (depth0 != null ? depth0.exportURL : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"exportURL","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.exportGroupDetailsTXT || (depth0 != null ? depth0.exportGroupDetailsTXT : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"exportGroupDetailsTXT","hash":{},"data":data}) : helper)))
    + "<span class=\"icon icon-external\"></span></a></div>\n";
},"9":function(container,depth0,helpers,partials,data) {
    var helper;

  return " ("
    + container.escapeExpression(((helper = (helper = helpers.memberCount || (depth0 != null ? depth0.memberCount : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"memberCount","hash":{},"data":data}) : helper)))
    + ")";
},"11":function(container,depth0,helpers,partials,data) {
    var stack1;

  return "		<table>\n			<tbody>\n"
    + ((stack1 = helpers["if"].call(depth0 != null ? depth0 : (container.nullContext || {}),((stack1 = (depth0 != null ? depth0.group : depth0)) != null ? stack1.ldaporg_canedit : stack1),{"name":"if","hash":{},"fn":container.program(12, data, 0),"inverse":container.program(20, data, 0),"data":data})) != null ? stack1 : "")
    + "			</tbody>\n		</table>\n";
},"12":function(container,depth0,helpers,partials,data) {
    var stack1;

  return ((stack1 = helpers.each.call(depth0 != null ? depth0 : (container.nullContext || {}),((stack1 = (depth0 != null ? depth0.group : depth0)) != null ? stack1.ldaporg_members : stack1),{"name":"each","hash":{},"fn":container.program(13, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "");
},"13":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "					<tr class=\"members-menu\">\n						<td>"
    + alias4(((helper = (helper = helpers.ldapcontacts_name || (depth0 != null ? depth0.ldapcontacts_name : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"ldapcontacts_name","hash":{},"data":data}) : helper)))
    + " "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.ldaporg_admin : depth0),{"name":"if","hash":{},"fn":container.program(14, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + " </td>\n						<td>\n							<a href=\"#\" class=\"icon icon-more\"></a>\n							<div class=\"hidden options\">\n								<ul>\n									"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.ldaporg_admin : depth0),{"name":"if","hash":{},"fn":container.program(16, data, 0),"inverse":container.program(18, data, 0),"data":data})) != null ? stack1 : "")
    + "									<li><a href=\"#\" class=\"remove\" data-id=\""
    + alias4(((helper = (helper = helpers.ldapcontacts_entry_id || (depth0 != null ? depth0.ldapcontacts_entry_id : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"ldapcontacts_entry_id","hash":{},"data":data}) : helper)))
    + "\" data-action=\"remove\"><span class=\"icon icon-delete\"></span><span>"
    + alias4(((helper = (helper = helpers.removeTXT || (depth0 != null ? depth0.removeTXT : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"removeTXT","hash":{},"data":data}) : helper)))
    + "</span></a></li>\n								</ul>\n							</div>\n						</td>\n					</tr>\n";
},"14":function(container,depth0,helpers,partials,data) {
    var helper;

  return "<i class=\"fa fa-user-circle\" aria-hidden=\"true\" title=\""
    + container.escapeExpression(((helper = (helper = helpers.groupAdminTXT || (depth0 != null ? depth0.groupAdminTXT : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"groupAdminTXT","hash":{},"data":data}) : helper)))
    + "\"></i>";
},"16":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<li><a href=\"#\" class=\"remove-admin\" data-id=\""
    + alias4(((helper = (helper = helpers.ldapcontacts_entry_id || (depth0 != null ? depth0.ldapcontacts_entry_id : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"ldapcontacts_entry_id","hash":{},"data":data}) : helper)))
    + "\" data-action=\"removeAdmin\"><i class=\"fa fa-user-times\" aria-hidden=\"true\"></i><span>"
    + alias4(((helper = (helper = helpers.removeAdminPrivTXT || (depth0 != null ? depth0.removeAdminPrivTXT : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"removeAdminPrivTXT","hash":{},"data":data}) : helper)))
    + "</span></a></li>\n									";
},"18":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<li><a href=\"#\" class=\"add-admin\" data-id=\""
    + alias4(((helper = (helper = helpers.ldapcontacts_entry_id || (depth0 != null ? depth0.ldapcontacts_entry_id : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"ldapcontacts_entry_id","hash":{},"data":data}) : helper)))
    + "\" data-action=\"addAdmin\"><i class=\"fa fa-user-plus\" aria-hidden=\"true\"></i><span>"
    + alias4(((helper = (helper = helpers.makeAdminTXT || (depth0 != null ? depth0.makeAdminTXT : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"makeAdminTXT","hash":{},"data":data}) : helper)))
    + "</span></a></li>\n";
},"20":function(container,depth0,helpers,partials,data) {
    var stack1;

  return ((stack1 = helpers.each.call(depth0 != null ? depth0 : (container.nullContext || {}),((stack1 = (depth0 != null ? depth0.group : depth0)) != null ? stack1.ldaporg_members : stack1),{"name":"each","hash":{},"fn":container.program(21, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "");
},"21":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {});

  return "					<tr>\n						<td>"
    + container.escapeExpression(((helper = (helper = helpers.ldapcontacts_name || (depth0 != null ? depth0.ldapcontacts_name : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(alias1,{"name":"ldapcontacts_name","hash":{},"data":data}) : helper)))
    + " "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.ldaporg_admin : depth0),{"name":"if","hash":{},"fn":container.program(14, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + " </td>\n						<td>"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.ldaporg_admin : depth0),{"name":"if","hash":{},"fn":container.program(22, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "</td>\n					</tr>\n";
},"22":function(container,depth0,helpers,partials,data) {
    return "<span class=\"admin\"></span>";
},"24":function(container,depth0,helpers,partials,data) {
    var helper;

  return "		<h4>"
    + container.escapeExpression(((helper = (helper = helpers.noMembersTXT || (depth0 != null ? depth0.noMembersTXT : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"noMembersTXT","hash":{},"data":data}) : helper)))
    + "</h4>\n";
},"26":function(container,depth0,helpers,partials,data) {
    var helper;

  return "	<h3>"
    + container.escapeExpression(((helper = (helper = helpers.selectGroupTXT || (depth0 != null ? depth0.selectGroupTXT : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"selectGroupTXT","hash":{},"data":data}) : helper)))
    + "</h3><span class=\"msg\"></span>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1;

  return ((stack1 = helpers["if"].call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.group : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.program(26, data, 0),"data":data})) != null ? stack1 : "");
},"useData":true});
templates['main_navigation'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1;

  return ((stack1 = helpers.each.call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.groups : depth0),{"name":"each","hash":{},"fn":container.program(2, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "");
},"2":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "			<li class=\"group "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.active : depth0),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\"  data-id=\""
    + alias4(((helper = (helper = helpers.ldapcontacts_entry_id || (depth0 != null ? depth0.ldapcontacts_entry_id : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"ldapcontacts_entry_id","hash":{},"data":data}) : helper)))
    + "\">\n				<a href=\"#\" class=\"load\">"
    + alias4(((helper = (helper = helpers.ldapcontacts_name || (depth0 != null ? depth0.ldapcontacts_name : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"ldapcontacts_name","hash":{},"data":data}) : helper)))
    + "</a>\n				"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.superuser : depth0),{"name":"if","hash":{},"fn":container.program(5, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\n			</li>\n";
},"3":function(container,depth0,helpers,partials,data) {
    return "active";
},"5":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<a><span class=\"icon icon-delete\" title=\""
    + alias4(((helper = (helper = helpers.deleteTXT || (depth0 != null ? depth0.deleteTXT : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"deleteTXT","hash":{},"data":data}) : helper)))
    + "\" data-id=\""
    + alias4(((helper = (helper = helpers.ldapcontacts_entry_id || (depth0 != null ? depth0.ldapcontacts_entry_id : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"ldapcontacts_entry_id","hash":{},"data":data}) : helper)))
    + "\"></span></a>";
},"7":function(container,depth0,helpers,partials,data) {
    var helper;

  return "		<li><a>"
    + container.escapeExpression(((helper = (helper = helpers.noMemberTXT || (depth0 != null ? depth0.noMemberTXT : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"noMemberTXT","hash":{},"data":data}) : helper)))
    + "</li>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1;

  return "<ul>\n"
    + ((stack1 = helpers["if"].call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.groups : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.program(7, data, 0),"data":data})) != null ? stack1 : "")
    + "</ul>\n";
},"useData":true});
templates['main_navigation_header'] = template({"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper;

  return "<a href=\"#\" id=\"add-group\"><i class=\"fa fa-plus-square\"></i><span>"
    + container.escapeExpression(((helper = (helper = helpers.addGroupTXT || (depth0 != null ? depth0.addGroupTXT : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"addGroupTXT","hash":{},"data":data}) : helper)))
    + "</span></a>\n";
},"useData":true});
templates['main_remove_group'] = template({"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper;

  return "<div>\n	<h2>"
    + container.escapeExpression(((helper = (helper = helpers.questionTXT || (depth0 != null ? depth0.questionTXT : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"questionTXT","hash":{},"data":data}) : helper)))
    + "</h2>\n	<div><span class=\"msg\"></span></div>\n\n	<div>\n		<button id=\"remove-group\"><?php p($l->t( 'Yes' )); ?></button>\n		<button id=\"abort-remove-group\"><?php p($l->t( 'No' )); ?></button>\n	</div>\n</div>\n";
},"useData":true});
templates['main_tutorial'] = template({"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<div id=\"tutorial-container\" style=\"display: none\">\n	<div class=\"body\">\n		"
    + alias4(((helper = (helper = helpers.message || (depth0 != null ? depth0.message : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"message","hash":{},"data":data}) : helper)))
    + "\n	</div>\n	<div class=\"footer\">\n		<button id=\"tutorial-next\">"
    + alias4(((helper = (helper = helpers.gotItTXT || (depth0 != null ? depth0.gotItTXT : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"gotItTXT","hash":{},"data":data}) : helper)))
    + "</button>\n	</div>\n</div>\n";
},"useData":true});
})();