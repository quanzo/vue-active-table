module.exports = {
    ActiveTable: function (name = "ActiveTable") {
        const TBL = require('./active-table.vue');
        return Object.assign(TBL.default, { "name": name });
    }
};