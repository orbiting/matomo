/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
(function () {
    angular.module('piwikApp').controller('NotificationController', NotificationController);

    NotificationController.$inject = ['piwikApi'];

    function NotificationController(piwikApi) {
        /**
         * Marks a persistent notification as read so it will not reappear on the next page
         * load.
         */
        this.markNotificationAsRead = function (notificationId) {
            if (!notificationId) {
                return;
            }

            piwikApi.withTokenInUrl();
            piwikApi.post(
                { // GET params
                    module: 'CoreHome',
                    action: 'markNotificationAsRead'
                },
                { // POST params
                    notificationId: notificationId
                }
            );
        };
    }
})();