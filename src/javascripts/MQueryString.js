var MQueryString = function ()
{
    this.map = {};

    var outerThis = this;

    location.search.replace(
            new RegExp("([^?=&]+)(=([^&]*))?", "g"),
            function ($0, $1, $2, $3) {
                outerThis.map[$1] = $3;
            }
    );
};

/**
 * Returns the value of the key in query string.
 * 
 * @param {string} key
 * @returns null|string
 */
MQueryString.prototype.getValue = function (key)
{
    var toReturn = this.map[key];

    if (typeof toReturn === "undefined")
    {
        return null;
    }

    return toReturn;
};