var app = angular.module("sermonArchiveApp", []);

app.controller("sermonController", function($scope, $http) {
  $http.get("/data.json").success(function(data)  {
    $scope.sermons = data;
  });
});

app.controller("sermonCovers", function($scope, $http) {
  $http.get("/featured.json").success(function(data)  {
    $scope.featured = data;
  });
});

app.controller("introText", function($scope) {
  $scope.text='<p>Welcome to the SPEP Sermon Archive. Here, you will find pretty much every sermon preached in the last 10 years, in addition to a collection of bonus media. We\'re testing out a new interface, and are interested in your feedback. Send us a message on <a href="https://facebook.com/SpepMedia/">Facebook</a> or <a href="http://spepchurch.org/contactus/contact_media.html">Drop us an email</a></p> <p>To Play a file, simply click on it. To Download a file, right click it and select "Save Link As..." or "Save Target As...". </p> '
});
