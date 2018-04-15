<% foreach([1,2,3] as $number): %>
    <%= $number %>
<% endforeach; %>

<% foreach([1,2,3] as $number): %>
    number
<% endforeach; %>

This should make no difference:
            <%- foreach([1,2,3] as $number): %>
    <%= $number %>
            <%- endforeach; %>
And the previous line should not have leading whitespace.
