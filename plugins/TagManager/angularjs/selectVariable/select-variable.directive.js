/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

/**
 * Usage:
 * <div piwik-variable-select>
 */
(function () {
    angular.module('piwikApp').directive('piwikVariableSelect', piwikVariableSelect);

    piwikVariableSelect.$inject = ['piwik'];

    function piwikVariableSelect(piwik){

        return {
            restrict: 'A',
            scope: {
                idContainer: '=',
                idContainerVersion: '=',
                onSelectVariable: '&?'
            },
            templateUrl: 'plugins/TagManager/angularjs/selectVariable/select-variable.directive.html?cb=' + piwik.cacheBuster,
            controller: 'VariableSelectController',
            controllerAs: 'variableSelect'
        };
    }
})();