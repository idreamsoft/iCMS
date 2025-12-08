define(["cms"], function (cms) {
    const factory = {
        init: function (param,method) {
            const {iv} = param;
            iCMS.run(`${iv[2]}/js/ui`, iv[3], param);
            return true;
        }
    };
    return factory;
});