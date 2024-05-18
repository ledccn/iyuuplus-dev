<!--- BEGIN HEADER -->
# Changelog

All notable changes to this project will be documented in this file.
<!--- END HEADER -->

<a name="unreleased"></a>
## [Unreleased]


<a name="2.8.0"></a>
## [2.8.0] - 2024-05-10
### Feat
- **WPush:** Add WPush package with Authenticator, Client, Message classes and README.md file
- **tests:** add ClientTest for WPush


<a name="2.7.0"></a>
## [2.7.0] - 2024-05-10
### Docs
- **readme:** update push notification SDK list

### Feat
- **AnPush:** add ClientTest.php for sending message
- **AnPush:** Add AnPush Authenticator, Client, and Message classes


<a name="2.6.1"></a>
## [2.6.1] - 2024-05-09
### Pull Requests
- Merge pull request [#77](https://github.com/guanguans/notify/issues/77) from guanguans/dependabot/github_actions/trufflesecurity/trufflehog-3.75.1
- Merge pull request [#76](https://github.com/guanguans/notify/issues/76) from guanguans/dependabot/github_actions/trufflesecurity/trufflehog-3.75.0
- Merge pull request [#75](https://github.com/guanguans/notify/issues/75) from guanguans/dependabot/github_actions/dependabot/fetch-metadata-2.1.0
- Merge pull request [#74](https://github.com/guanguans/notify/issues/74) from guanguans/dependabot/github_actions/trufflesecurity/trufflehog-3.74.0


<a name="2.6.0"></a>
## [2.6.0] - 2024-04-22
### Test
- Add tests for getting headers and reason in Response class

### Pull Requests
- Merge pull request [#72](https://github.com/guanguans/notify/issues/72) from guanguans/dependabot/github_actions/trufflesecurity/trufflehog-3.73.0
- Merge pull request [#71](https://github.com/guanguans/notify/issues/71) from guanguans/dependabot/github_actions/trufflesecurity/trufflehog-3.72.0
- Merge pull request [#70](https://github.com/guanguans/notify/issues/70) from guanguans/dependabot/github_actions/trufflesecurity/trufflehog-3.71.2
- Merge pull request [#69](https://github.com/guanguans/notify/issues/69) from guanguans/dependabot/github_actions/trufflesecurity/trufflehog-3.71.1
- Merge pull request [#68](https://github.com/guanguans/notify/issues/68) from guanguans/dependabot/github_actions/trufflesecurity/trufflehog-3.71.0


<a name="2.5.1"></a>
## [2.5.1] - 2024-03-22
### Refactor
- **Response:** improve request and response summary handling

### Pull Requests
- Merge pull request [#67](https://github.com/guanguans/notify/issues/67) from guanguans/dependabot/github_actions/dependabot/fetch-metadata-2.0.0
- Merge pull request [#66](https://github.com/guanguans/notify/issues/66) from guanguans/dependabot/github_actions/trufflesecurity/trufflehog-3.70.3


<a name="2.5.0"></a>
## [2.5.0] - 2024-03-20
### Feat
- **SimplePush:** Add SimplePush package with Authenticator, Client, and Messages

### Test
- **SimplePush:** add test case for sending message


<a name="2.4.3"></a>
## [2.4.3] - 2024-03-20
### Pull Requests
- Merge pull request [#65](https://github.com/guanguans/notify/issues/65) from guanguans/dependabot/github_actions/trufflesecurity/trufflehog-3.70.2
- Merge pull request [#64](https://github.com/guanguans/notify/issues/64) from guanguans/dependabot/github_actions/trufflesecurity/trufflehog-3.70.0


<a name="2.4.2"></a>
## [2.4.2] - 2024-03-12
### CI
- apply php-cs-fixer

### Docs
- **readme:** Add list of supported Push notification SDKs

### Refactor
- **utils:** Improve property retrieval in Utils class
- **utils:** improve handling of properties in Utils class


<a name="2.4.1"></a>
## [2.4.1] - 2024-03-11
### Docs
- Update push notification SDK list in README

### Feat
- **Utils:** Add method to get defined properties for an object

### Refactor
- **HasOptions:** Improve preConfigureOptionsResolver method
- **platform-lint:** Update platform handling and documentation
- **tests:** Improve HasOptionsTest.php test coverage
- **utils:** Use Utils::definedFor() method in HasOptions


<a name="2.4.0"></a>
## [2.4.0] - 2024-03-10
### CI
- apply php-cs-fixer

### Feat
- **helper:** add error_silencer function
- **src:** Add Method interface and related RFCs
- **tests:** add Faker trait for generating fake data

### Refactor
- improve StringToClassConstantRector
- **rector:** Update namespace for rector classes
- **utils:** Rename getHttpOptionsConstants to httpOptionConstants
- **utils:** improve user agent method
- **utils:** Improve clarity and consistency in multipartFor method

### Pull Requests
- Merge pull request [#61](https://github.com/guanguans/notify/issues/61) from guanguans/dependabot/github_actions/trufflesecurity/trufflehog-3.69.0


<a name="2.3.1"></a>
## [2.3.1] - 2024-03-08
### Feat
- **Utils:** Add userAgent method to generate user agent string
- **httpclient:** Add User-Agent header to HTTP requests

### Refactor
- **Authenticator:** improve constructor logic
- **HasHttpClient:** adjust multipart options handling
- **HasHttpClient:** Improve HTTP client options handling
- **Utils:** improve multipartFor method
- **Utils:** Improve handling of contents in Utils class

### Test
- **AuthenticatorTest:** add tests for Authenticator class


<a name="2.3.0"></a>
## [2.3.0] - 2024-03-08
### Feat
- **pushbullet:** add PushBullet integration

### Test
- **PushBullet:** add test for sending message functionality

### Pull Requests
- Merge pull request [#60](https://github.com/guanguans/notify/issues/60) from guanguans/dependabot/github_actions/trufflesecurity/trufflehog-3.68.5


<a name="2.2.1"></a>
## [2.2.1] - 2024-03-07
### Docs
- **README:** Update baseUri parameter in code comments

### Feat
- **Str:** Add kebab case conversion method
- **php:** Add StringToClassConstantRector for GuzzleHttp RequestOptions
- **platform-lint:** Add platform-lint script for linting platforms
- **tests:** add new test files for Foundation

### Refactor
- **Message:** Improve JSON serialization method
- **auth:** Improve CompactToVariablesRector
- **debug:** Use mergeDebugInfo instead of withDebugInfo
- **http:** Normalize HTTP options handling
- **options:** Improve options handling and validation
- **uri:** improve UriTemplateAuthenticator applyToRequest method

### Test
- **MessageTest:** add validation for options


<a name="2.2.0"></a>
## [2.2.0] - 2024-03-06
### Feat
- **Client:** Add __debugInfo method for debugging
- **HasHttpClient:** Add ability to set HttpClientResolver
- **tests:** add AsNullUri to Message class and update PostMessage structure

### Refactor
- **AsBody:** improve handling of message body serialization
- **HasHttpClient:** configure HTTP options in getHttpClient method
- **HasOptions:** Improve options handling
- **core:** Update toPayload method in Message classes
- **core:** Improve payload handling in HTTP request options
- **http:** improve HTTP client configuration
- **rectors:** Move rector classes to appropriate namespace


<a name="2.1.2"></a>
## [2.1.2] - 2024-03-05
### Feat
- **NewsMessage:** add method to configure options for articles
- **auth:** add OptionsAuthenticator and mergeHttpOptions method

### Refactor
- Remove unnecessary trait use in rector.php
- **HasOptionsDocCommentRector:** Sort properties in alphabetical order
- **Message:** Improve attachment handling in Message class
- **Message:** improve default options handling
- **Message:** Improve addAction method and configureOptionsResolver
- **Message:** Improve embed configuration handling
- **commit:** refactor configureAndResolveOptions method
- **core:** update HTTP method definitions
- **message:** Refactor message classes for NowPush
- **message:** Improve section and potential action handling
- **postmessage:** Improve language-specific post setting

### Pull Requests
- Merge pull request [#59](https://github.com/guanguans/notify/issues/59) from guanguans/dependabot/github_actions/trufflesecurity/trufflehog-3.68.4


<a name="2.1.1"></a>
## [2.1.1] - 2024-03-04
### Docs
- Update push notification SDK list

### Feat
- **Telegram:** Add support for multipart requests in Telegram messages
- **dingtalk:** add DingTalk notification support
- **extract-platform:** add script to extract platform information
- **messages:** Add toHttpUri method for sending various message types
- **telegram:** Add AsJson trait to GetUpdatesMessage and ClientTest
- **telegram:** Add constructor to Client class

### Refactor
- **client:** Remove Makeable trait from Client class
- **media:** Improve media handling in MediaGroupMessage
- **message:** Improve make method in Message class
- **pushplus:** Rename Pushplus to PushPlus and update namespaces


<a name="2.1.0"></a>
## [2.1.0] - 2024-03-03
### Feat
- **PushDeer:** Add PushDeer service with related classes

### Refactor
- **tests:** improve assertCanSendMessage method


<a name="2.0.6"></a>
## [2.0.6] - 2024-03-02
### Feat
- **Message:** add delayMilliseconds and url methods
- **Slack:** Add metadata to Message options
- **Zulip:** add Message class with message creation methods
- **messages:** add type, date, and time properties
- **messages:** Add support for FileMessage and TemplateCardMessage

### Refactor
- **Message:** Remove redundant array definition
- **TemplateCardMessage:** optimize options configuration
- **message:** Update message classes structure


<a name="2.0.5"></a>
## [2.0.5] - 2024-03-02
### Docs
- **ShowdocPush:** Update ShowdocPush documentation
- **slack:** Update Slack Message class methods and properties

### Feat
- **Message:** Add channel, webhook, callbackUrl, and timestamp properties to Message class
- **QQ:** add constructor to Client class
- **RocketChat:** Add message sending functionality with attachments

### Test
- **ClientTest:** Add new message fields
- **ClientTest:** refactor message creation in ClientTest
- **ClientTest:** Add attachment and device to message


<a name="2.0.4"></a>
## [2.0.4] - 2024-03-01
### Feat
- **Push:** add support for different message types

### Refactor
- **Response:** Improve handling of decoded JSON response
- **httpclient:** optimize setting http options
- **messages:** Update message classes with new traits and methods
- **push:** Update Authenticator to use AggregateAuthenticator and PayloadAuthenticator
- **pushback:** Remove commented out code and update URLs in README

### Test
- **ClientTest:** update message sending feature


<a name="2.0.3"></a>
## [2.0.3] - 2024-02-29
### Docs
- **readme:** update Gitter links and remove Gitter from phpunit.xml.dist

### Feat
- **GoogleChat:** Add compact payload creation in Authenticator
- **IGot:** Add title, url, copy, and detail to message
- **Message:** Add support for [@type](https://github.com/type) and [@context](https://github.com/context) in Message
- **MicrosoftTeams:** Add test for sending message

### Refactor
- **ClientTest:** Refactor test cases for sending messages
- **Message:** Remove base_uri usage and update toHttpUri method
- **Message:** Improve Message class structure
- **Message:** Optimize 'toHttpOptions' method
- **arr:** Improve array handling in Arr class
- **messages:** Improve array filtering in Message class
- **tests:** improve custom expectation function naming
- **types:** Update type declarations to bool in message classes

### Test
- Add Mattermost ClientTest and update Message.php
- Add Gitter and GoogleChat ClientTest files

### Pull Requests
- Merge pull request [#58](https://github.com/guanguans/notify/issues/58) from guanguans/dependabot/github_actions/trufflesecurity/trufflehog-3.68.3


<a name="2.0.2"></a>
## [2.0.2] - 2024-02-28
### Feat
- **Chanify:** add support for audio, file, and image messages
- **Discord:** Add ability to send messages with rich content
- **Pest:** add assertCanSendMessage expectation

### Refactor
- **Message:** update message class properties and methods
- **MockHandler:** Remove unnecessary code and improve readability
- **tests:** Update ClientTest.php for message sending functionality

### Test
- **ClientTest:** Refactor message creation in ClientTest


<a name="2.0.1"></a>
## [2.0.1] - 2024-02-27
### Docs
- Add README.md files for various messaging platforms
- **HasOptions:** Add support for nested options

### Feat
- **notifications:** Add Bark notification support
- **options:** Add support for ignoreUndefined property
- **support:** Improve array handling in HasHttpClient

### Refactor
- **HasOptions:** Improve handling of defined and required options
- **HasOptions:** update deprecated options handling
- **tests:** rename wsse authenticator test file and add certificate authenticator test

### Test
- Add unit tests for Client and Message classes
- add CertificateAuthenticatorTest and WsseAuthenticatorTest

### Pull Requests
- Merge pull request [#57](https://github.com/guanguans/notify/issues/57) from guanguans/dependabot/github_actions/trufflesecurity/trufflehog-3.68.2


<a name="2.0.0"></a>
## [2.0.0] - 2024-02-26
### Feat
- **Message:** Add __debugInfo method
- **Utils:** Add objectWithDebugInfo method

### Refactor
- **concerns:** Update mixin references in traits
- **debug:** Improve debug info handling


<a name="2.0.0-rc1"></a>
## [2.0.0-rc1] - 2024-02-23
### Feat
- **composer:** add illuminate/collections dependency

### Refactor
- **HasOptions:** Remove resolveOptions method and update getOption method
- **Response:** improve response class structure and add new methods
- **http:** Ensure required middleware in HasHttpClient trait
- **utils:** Rename method to more descriptive name


<a name="2.0.0-beta3"></a>
## [2.0.0-beta3] - 2024-02-22
### Feat
- **Notify:** Add WithDumpable trait and dump method
- **Response:** Add __debugInfo method for Response class
- **ToInternalExceptionRector:** Add ToInternalExceptionRector rule
- **ToInternalExceptionRector:** Add ToInternalExceptionRector for internal exceptions handling

### Refactor
- **Exceptions:** Remove unused exception classes
- **src:** Improve code readability and remove redundant functions
- **src:** Update ToInternalExceptionRector to handle new class nodes

### Pull Requests
- Merge pull request [#56](https://github.com/guanguans/notify/issues/56) from guanguans/dependabot/github_actions/trufflesecurity/trufflehog-3.68.0


<a name="2.0.0-beta2"></a>
## [2.0.0-beta2] - 2024-02-21
### Feat
- **ClientTest:** add support for sending different types of messages
- **YiFengChuanHua:** add ClientTest.php for sending message
- **utils:** Add Utils class for converting form array into multipart array
- **zulip:** add test for sending direct message

### Refactor
- **client:** remove Conditionable and Tappable traits
- **concerns:** rename Traits to Concerns
- **doc:** Update Http client doc comment
- **doc:** Has options doc comment
- **utils:** Improve readability of getHttpOptionsConstants method

### Test
- **Chanify:** Add tests for sending text and link messages
- **ClientTest:** Add test case for sending message
- **ClientTest:** Add test case for sending message
- **ClientTest:** Add test for sending text message
- **ClientTest:** add test for sending message functionality
- **ClientTest:** add test for sending text messages
- **Discord:** add test case for sending message
- **IGot:** Add test case for sending message
- **Push:** Add ClientTest.php for message sending functionality
- **PushBack:** add ClientTest.php for testing sending messages
- **PushPlus:** Add test for sending message
- **Pushover:** add test for sending message
- **RocketChat:** add test case for sending message
- **ServerChan:** Add ClientTest.php for ServerChan client testing
- **Showdoc:** Add test for sending message
- **Slack:** Add test case for sending a message
- **WeWorkGroupBot:** add test for sending message
- **XiZhi:** add single and channel message test cases
- **telegram:** Add test case for sending a message via Telegram client

### Pull Requests
- Merge pull request [#55](https://github.com/guanguans/notify/issues/55) from guanguans/dependabot/github_actions/trufflesecurity/trufflehog-3.67.7
- Merge pull request [#51](https://github.com/guanguans/notify/issues/51) from guanguans/dependabot/github_actions/codecov/codecov-action-4
- Merge pull request [#54](https://github.com/guanguans/notify/issues/54) from guanguans/dependabot/github_actions/trufflesecurity/trufflehog-3.67.6


<a name="2.0.0-beta1"></a>
## [2.0.0-beta1] - 2024-02-19
### Docs
- Update links and add examples for RocketChat and Slack clients

### Feat
- **Client:** add async method for sending messages
- **DocCommentRector:** Add DocCommentRector class
- **HasHttpClient:** Add mock method
- **Notify:** add delete, head, patch, and put methods
- **bark:** add Bark client, credential, and message classes
- **client:** Add EnsureResponse middleware
- **commit:** Add Conditionable and Macroable traits
- **credentials:** add AggregateCredential class
- **credentials:** add CertificateCredential class
- **github:** add secrets check workflow
- **mock:** add MockHandler class
- **tests:** add XiZhi\ClientTest

### Refactor
- Improve DocCommentRector and HasOptions
- Update credentials classes
- **Credentials:** Update ApiKeyCredential, DigestAuthCredential, HeaderCredential, and QueryCredential
- **HasHttpClient:** refactor mock method
- **Message:** Update toHttpUri() method
- **MockHandler:** improve MockHandler class structure and functionality
- **Response:** improve Response class
- **Str:** Improve case conversion methods
- **Traits:** Improve HasOptions trait
- **UpdateHasHttpClientDocCommentRector:** Update createMethodPhpDocTagNode method
- **client:** remove unused methods and add missing method
- **client:** Refactor the Client class
- **concerns:** Update options handling in traits
- **concerns:** Remove unused imports
- **config:** update PHP version and sets
- **credentials:** refactor credential classes
- **credentials:** refactor BasicAuthCredential, CallbackCredential, DigestAuthCredential, NtlmAuthCredential, and WsseAuthCredential classes
- **handler:** refactor MockHandler constructor and response handling
- **headercredential:** refactor applyToRequest method
- **helper:** Refactor to_multipart function
- **http-client:** Refactor HasHttpClient trait
- **httpclient:** refactor send method
- **middleware:** Refactor ApplyAuthenticatorToRequest and EnsureResponse middleware
- **options:** Improve options handling in Traits

### Pull Requests
- Merge pull request [#53](https://github.com/guanguans/notify/issues/53) from guanguans/1.x
- Merge pull request [#52](https://github.com/guanguans/notify/issues/52) from guanguans/dependabot/composer/rector/rector-tw-0.19or-tw-1.0
- Merge pull request [#50](https://github.com/guanguans/notify/issues/50) from guanguans/dependabot/github_actions/actions/setup-node-4
- Merge pull request [#49](https://github.com/guanguans/notify/issues/49) from guanguans/dependabot/github_actions/actions/cache-4


<a name="1.28.0"></a>
## [1.28.0] - 2024-01-17

<a name="1.27.2"></a>
## [1.27.2] - 2024-01-17
### Feat
- **.zhlintrc:** add .zhlintrc file

### Test
- **rocketChat:** Skip RocketChat test

### Pull Requests
- Merge pull request [#48](https://github.com/guanguans/notify/issues/48) from guanguans/dependabot/composer/rector/rector-tw-0.17or-tw-0.18or-tw-0.19
- Merge pull request [#47](https://github.com/guanguans/notify/issues/47) from guanguans/dependabot/github_actions/actions/stale-9
- Merge pull request [#46](https://github.com/guanguans/notify/issues/46) from guanguans/dependabot/github_actions/actions/labeler-5


<a name="1.27.1"></a>
## [1.27.1] - 2023-10-19
### Docs
- **readme:** update README links


<a name="1.27.0"></a>
## [1.27.0] - 2023-10-07
### Fix
- **client:** Update PushPlus request URL

### Test
- **QqChannelBotTest:** Skip testQqChannelBot


<a name="1.26.1"></a>
## [1.26.1] - 2023-10-07
### Pull Requests
- Merge pull request [#45](https://github.com/guanguans/notify/issues/45) from guanguans/dependabot/github_actions/stefanzweifel/git-auto-commit-action-5
- Merge pull request [#43](https://github.com/guanguans/notify/issues/43) from guanguans/dependabot/github_actions/codecov/codecov-action-4
- Merge pull request [#42](https://github.com/guanguans/notify/issues/42) from guanguans/dependabot/github_actions/actions/checkout-4
- Merge pull request [#40](https://github.com/guanguans/notify/issues/40) from guanguans/dependabot/composer/rector/rector-tw-0.17or-tw-0.18


<a name="1.26.0"></a>
## [1.26.0] - 2023-08-16
### CI
- **psalm.xml.dist:** update psalm config file

### Docs
- **readme:** update README-EN.md and README.md

### Feat
- **monorepo-builder:** add new monorepo-builder.php file

### Fix
- **composer:** Resolve conflict with symplify/monorepo-builder

### Refactor
- **src:** apply rector

### Pull Requests
- Merge pull request [#38](https://github.com/guanguans/notify/issues/38) from guanguans/dependabot/github_actions/dependabot/fetch-metadata-1.6.0
- Merge pull request [#37](https://github.com/guanguans/notify/issues/37) from guanguans/dependabot/composer/dms/phpunit-arraysubset-asserts-tw-0.4.0or-tw-0.5.0
- Merge pull request [#36](https://github.com/guanguans/notify/issues/36) from guanguans/dependabot/composer/rector/rector-tw-0.15.7or-tw-0.16.0or-tw-0.17.0
- Merge pull request [#35](https://github.com/guanguans/notify/issues/35) from guanguans/dependabot/github_actions/dependabot/fetch-metadata-1.5.1
- Merge pull request [#34](https://github.com/guanguans/notify/issues/34) from guanguans/dependabot/github_actions/dependabot/fetch-metadata-1.5.0
- Merge pull request [#33](https://github.com/guanguans/notify/issues/33) from guanguans/dependabot/composer/rector/rector-tw-0.15.7or-tw-0.16.0
- Merge pull request [#32](https://github.com/guanguans/notify/issues/32) from guanguans/dependabot/github_actions/dependabot/fetch-metadata-1.4.0
- Merge pull request [#30](https://github.com/guanguans/notify/issues/30) from guanguans/dependabot/github_actions/dependabot/fetch-metadata-1.3.6


<a name="v1.25.1"></a>
## [v1.25.1] - 2023-01-19

<a name="v1.25.0"></a>
## [v1.25.0] - 2023-01-18
### Pull Requests
- Merge pull request [#29](https://github.com/guanguans/notify/issues/29) from teakong/feature-yifengchuanhua
- Merge pull request [#26](https://github.com/guanguans/notify/issues/26) from guanguans/dependabot/composer/rector/rector-tw-0.14or-tw-0.15


<a name="v1.24.1"></a>
## [v1.24.1] - 2022-11-15

<a name="v1.24.0"></a>
## [v1.24.0] - 2022-11-14

<a name="v1.23.0"></a>
## [v1.23.0] - 2022-11-14
### Pull Requests
- Merge pull request [#24](https://github.com/guanguans/notify/issues/24) from guanguans/dependabot/github_actions/dependabot/fetch-metadata-1.3.5


<a name="v1.22.2"></a>
## [v1.22.2] - 2022-11-02

<a name="v1.22.1"></a>
## [v1.22.1] - 2022-11-01

<a name="v1.22.0"></a>
## [v1.22.0] - 2022-11-01
### Pull Requests
- Merge pull request [#23](https://github.com/guanguans/notify/issues/23) from guanguans/dependabot/github_actions/dependabot/fetch-metadata-1.3.4


<a name="v1.21.3"></a>
## [v1.21.3] - 2022-09-12

<a name="v1.21.2"></a>
## [v1.21.2] - 2022-09-11
### Pull Requests
- Merge pull request [#21](https://github.com/guanguans/notify/issues/21) from guanguans/dependabot/github_actions/dependabot/fetch-metadata-1.3.3
- Merge pull request [#20](https://github.com/guanguans/notify/issues/20) from guanguans/dependabot/github_actions/dependabot/fetch-metadata-1.3.2


<a name="v1.21.1"></a>
## [v1.21.1] - 2022-06-09

<a name="v1.21.0"></a>
## [v1.21.0] - 2022-06-09

<a name="v1.20.1"></a>
## [v1.20.1] - 2022-06-09
### Pull Requests
- Merge pull request [#17](https://github.com/guanguans/notify/issues/17) from guanguans/dependabot/github_actions/dependabot/fetch-metadata-1.3.1
- Merge pull request [#16](https://github.com/guanguans/notify/issues/16) from guanguans/dependabot/github_actions/codecov/codecov-action-3


<a name="v1.20.0"></a>
## [v1.20.0] - 2022-03-31

<a name="v1.19.2"></a>
## [v1.19.2] - 2022-03-28
### Pull Requests
- Merge pull request [#13](https://github.com/guanguans/notify/issues/13) from guanguans/issue-12-替换wrench/wrench为Textalk/websocket-php


<a name="v1.19.1"></a>
## [v1.19.1] - 2022-03-26

<a name="v1.19.0"></a>
## [v1.19.0] - 2022-03-26

<a name="v1.18.2"></a>
## [v1.18.2] - 2022-03-23

<a name="v1.18.1"></a>
## [v1.18.1] - 2022-03-23

<a name="v1.18.0"></a>
## [v1.18.0] - 2022-03-22

<a name="v1.17.0"></a>
## [v1.17.0] - 2022-03-22

<a name="v1.16.0"></a>
## [v1.16.0] - 2022-03-22
### Pull Requests
- Merge pull request [#11](https://github.com/guanguans/notify/issues/11) from guanguans/dependabot/github_actions/actions/cache-3


<a name="v1.15.0"></a>
## [v1.15.0] - 2022-03-21

<a name="v1.14.1"></a>
## [v1.14.1] - 2022-03-21

<a name="v1.14.0"></a>
## [v1.14.0] - 2022-03-21
### Pull Requests
- Merge pull request [#10](https://github.com/guanguans/notify/issues/10) from devcto/pushdeer


<a name="v1.13.2"></a>
## [v1.13.2] - 2022-03-14
### Pull Requests
- Merge pull request [#8](https://github.com/guanguans/notify/issues/8) from guanguans/dependabot/github_actions/actions/checkout-3
- Merge pull request [#9](https://github.com/guanguans/notify/issues/9) from guanguans/dependabot/github_actions/dependabot/fetch-metadata-1.3.0
- Merge pull request [#7](https://github.com/guanguans/notify/issues/7) from guanguans/dependabot/github_actions/dependabot/fetch-metadata-1.2.1


<a name="v1.13.1"></a>
## [v1.13.1] - 2022-02-22

<a name="v1.13.0"></a>
## [v1.13.0] - 2022-02-22

<a name="v1.12.1"></a>
## [v1.12.1] - 2022-01-09

<a name="v1.12.0"></a>
## [v1.12.0] - 2022-01-09

<a name="v1.11.1"></a>
## [v1.11.1] - 2022-01-09

<a name="v1.11.0"></a>
## [v1.11.0] - 2022-01-08

<a name="v1.10.0"></a>
## [v1.10.0] - 2022-01-06

<a name="v1.9.1"></a>
## [v1.9.1] - 2022-01-06

<a name="v1.9.0"></a>
## [v1.9.0] - 2022-01-06

<a name="v1.8.2"></a>
## [v1.8.2] - 2022-01-06

<a name="v1.8.1"></a>
## [v1.8.1] - 2022-01-05

<a name="v1.8.0"></a>
## [v1.8.0] - 2022-01-05

<a name="v1.7.2"></a>
## [v1.7.2] - 2021-12-12

<a name="v1.7.1"></a>
## [v1.7.1] - 2021-12-12

<a name="v1.7.0"></a>
## [v1.7.0] - 2021-12-12

<a name="v1.6.0"></a>
## [v1.6.0] - 2021-12-11

<a name="v1.5.0"></a>
## [v1.5.0] - 2021-12-10

<a name="v1.4.4"></a>
## [v1.4.4] - 2021-12-10

<a name="v1.4.3"></a>
## [v1.4.3] - 2021-12-10

<a name="v1.4.2"></a>
## [v1.4.2] - 2021-12-10

<a name="v1.4.1"></a>
## [v1.4.1] - 2021-12-10

<a name="v1.4.0"></a>
## [v1.4.0] - 2021-12-09

<a name="v1.3.1"></a>
## [v1.3.1] - 2021-12-09

<a name="v1.3.0"></a>
## [v1.3.0] - 2021-12-09

<a name="v1.2.0"></a>
## [v1.2.0] - 2021-12-08

<a name="v1.1.2"></a>
## [v1.1.2] - 2021-12-08

<a name="v1.1.1"></a>
## [v1.1.1] - 2021-12-08

<a name="v1.1.0"></a>
## [v1.1.0] - 2021-12-07

<a name="v1.0.5"></a>
## [v1.0.5] - 2021-12-07

<a name="v1.0.4"></a>
## [v1.0.4] - 2021-10-24

<a name="v1.0.3"></a>
## [v1.0.3] - 2021-10-17
### Pull Requests
- Merge pull request [#4](https://github.com/guanguans/notify/issues/4) from guanguans/dependabot/composer/friendsofphp/php-cs-fixer-tw-2.16or-tw-3.0


<a name="v1.0.2"></a>
## [v1.0.2] - 2021-10-10
### Pull Requests
- Merge pull request [#3](https://github.com/guanguans/notify/issues/3) from guanguans/dependabot/composer/overtrue/phplint-tw-2.3or-tw-3.0
- Merge pull request [#1](https://github.com/guanguans/notify/issues/1) from guanguans/dependabot/github_actions/codecov/codecov-action-2.1.0
- Merge pull request [#2](https://github.com/guanguans/notify/issues/2) from guanguans/dependabot/composer/vimeo/psalm-tw-3.11or-tw-4.0


<a name="v1.0.1"></a>
## [v1.0.1] - 2021-05-16
### Docs
- Update docs

### Feat
- Add separate method for separate setting option

### Perf
- Perf messages
- Perf clients


<a name="v1.0.0"></a>
## v1.0.0 - 2021-05-16
### Docs
- Update docs
- Add global `tap` function
- Update README.md
- Update README.md

### Feat
- Add some messages for FeiShu
- Add FeiShu client
- Add WeWork client
- Add DingTalk client
- Add Bark client
- Add ServerChan client
- Add XiZhi client
- Add Chanify client

### Fix
- Fix message with secret

### Perf
- Perf ServerChan
- Perf XiZhi
- Perf WeWork
- Finish DingTalk
- Finish FeiShu
- Finish WeWork
- Finish ServerChan
- Finish XiZhi
- Finish Chanify
- Finish Bark

### Refactor
- Refactor
- Refactor
- Refactor FeiShu
- Refactor FeiShu client
- Refactor WeWork client
- Refactor clients
- Refactor Chanify client

### Test
- Finish tests


[Unreleased]: https://github.com/guanguans/notify/compare/2.8.0...HEAD
[2.8.0]: https://github.com/guanguans/notify/compare/2.7.0...2.8.0
[2.7.0]: https://github.com/guanguans/notify/compare/2.6.1...2.7.0
[2.6.1]: https://github.com/guanguans/notify/compare/2.6.0...2.6.1
[2.6.0]: https://github.com/guanguans/notify/compare/2.5.1...2.6.0
[2.5.1]: https://github.com/guanguans/notify/compare/2.5.0...2.5.1
[2.5.0]: https://github.com/guanguans/notify/compare/2.4.3...2.5.0
[2.4.3]: https://github.com/guanguans/notify/compare/2.4.2...2.4.3
[2.4.2]: https://github.com/guanguans/notify/compare/2.4.1...2.4.2
[2.4.1]: https://github.com/guanguans/notify/compare/2.4.0...2.4.1
[2.4.0]: https://github.com/guanguans/notify/compare/2.3.1...2.4.0
[2.3.1]: https://github.com/guanguans/notify/compare/2.3.0...2.3.1
[2.3.0]: https://github.com/guanguans/notify/compare/2.2.1...2.3.0
[2.2.1]: https://github.com/guanguans/notify/compare/2.2.0...2.2.1
[2.2.0]: https://github.com/guanguans/notify/compare/2.1.2...2.2.0
[2.1.2]: https://github.com/guanguans/notify/compare/2.1.1...2.1.2
[2.1.1]: https://github.com/guanguans/notify/compare/2.1.0...2.1.1
[2.1.0]: https://github.com/guanguans/notify/compare/2.0.6...2.1.0
[2.0.6]: https://github.com/guanguans/notify/compare/2.0.5...2.0.6
[2.0.5]: https://github.com/guanguans/notify/compare/2.0.4...2.0.5
[2.0.4]: https://github.com/guanguans/notify/compare/2.0.3...2.0.4
[2.0.3]: https://github.com/guanguans/notify/compare/2.0.2...2.0.3
[2.0.2]: https://github.com/guanguans/notify/compare/2.0.1...2.0.2
[2.0.1]: https://github.com/guanguans/notify/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/guanguans/notify/compare/2.0.0-rc1...2.0.0
[2.0.0-rc1]: https://github.com/guanguans/notify/compare/2.0.0-beta3...2.0.0-rc1
[2.0.0-beta3]: https://github.com/guanguans/notify/compare/2.0.0-beta2...2.0.0-beta3
[2.0.0-beta2]: https://github.com/guanguans/notify/compare/2.0.0-beta1...2.0.0-beta2
[2.0.0-beta1]: https://github.com/guanguans/notify/compare/1.28.0...2.0.0-beta1
[1.28.0]: https://github.com/guanguans/notify/compare/1.27.2...1.28.0
[1.27.2]: https://github.com/guanguans/notify/compare/1.27.1...1.27.2
[1.27.1]: https://github.com/guanguans/notify/compare/1.27.0...1.27.1
[1.27.0]: https://github.com/guanguans/notify/compare/1.26.1...1.27.0
[1.26.1]: https://github.com/guanguans/notify/compare/1.26.0...1.26.1
[1.26.0]: https://github.com/guanguans/notify/compare/v1.25.1...1.26.0
[v1.25.1]: https://github.com/guanguans/notify/compare/v1.25.0...v1.25.1
[v1.25.0]: https://github.com/guanguans/notify/compare/v1.24.1...v1.25.0
[v1.24.1]: https://github.com/guanguans/notify/compare/v1.24.0...v1.24.1
[v1.24.0]: https://github.com/guanguans/notify/compare/v1.23.0...v1.24.0
[v1.23.0]: https://github.com/guanguans/notify/compare/v1.22.2...v1.23.0
[v1.22.2]: https://github.com/guanguans/notify/compare/v1.22.1...v1.22.2
[v1.22.1]: https://github.com/guanguans/notify/compare/v1.22.0...v1.22.1
[v1.22.0]: https://github.com/guanguans/notify/compare/v1.21.3...v1.22.0
[v1.21.3]: https://github.com/guanguans/notify/compare/v1.21.2...v1.21.3
[v1.21.2]: https://github.com/guanguans/notify/compare/v1.21.1...v1.21.2
[v1.21.1]: https://github.com/guanguans/notify/compare/v1.21.0...v1.21.1
[v1.21.0]: https://github.com/guanguans/notify/compare/v1.20.1...v1.21.0
[v1.20.1]: https://github.com/guanguans/notify/compare/v1.20.0...v1.20.1
[v1.20.0]: https://github.com/guanguans/notify/compare/v1.19.2...v1.20.0
[v1.19.2]: https://github.com/guanguans/notify/compare/v1.19.1...v1.19.2
[v1.19.1]: https://github.com/guanguans/notify/compare/v1.19.0...v1.19.1
[v1.19.0]: https://github.com/guanguans/notify/compare/v1.18.2...v1.19.0
[v1.18.2]: https://github.com/guanguans/notify/compare/v1.18.1...v1.18.2
[v1.18.1]: https://github.com/guanguans/notify/compare/v1.18.0...v1.18.1
[v1.18.0]: https://github.com/guanguans/notify/compare/v1.17.0...v1.18.0
[v1.17.0]: https://github.com/guanguans/notify/compare/v1.16.0...v1.17.0
[v1.16.0]: https://github.com/guanguans/notify/compare/v1.15.0...v1.16.0
[v1.15.0]: https://github.com/guanguans/notify/compare/v1.14.1...v1.15.0
[v1.14.1]: https://github.com/guanguans/notify/compare/v1.14.0...v1.14.1
[v1.14.0]: https://github.com/guanguans/notify/compare/v1.13.2...v1.14.0
[v1.13.2]: https://github.com/guanguans/notify/compare/v1.13.1...v1.13.2
[v1.13.1]: https://github.com/guanguans/notify/compare/v1.13.0...v1.13.1
[v1.13.0]: https://github.com/guanguans/notify/compare/v1.12.1...v1.13.0
[v1.12.1]: https://github.com/guanguans/notify/compare/v1.12.0...v1.12.1
[v1.12.0]: https://github.com/guanguans/notify/compare/v1.11.1...v1.12.0
[v1.11.1]: https://github.com/guanguans/notify/compare/v1.11.0...v1.11.1
[v1.11.0]: https://github.com/guanguans/notify/compare/v1.10.0...v1.11.0
[v1.10.0]: https://github.com/guanguans/notify/compare/v1.9.1...v1.10.0
[v1.9.1]: https://github.com/guanguans/notify/compare/v1.9.0...v1.9.1
[v1.9.0]: https://github.com/guanguans/notify/compare/v1.8.2...v1.9.0
[v1.8.2]: https://github.com/guanguans/notify/compare/v1.8.1...v1.8.2
[v1.8.1]: https://github.com/guanguans/notify/compare/v1.8.0...v1.8.1
[v1.8.0]: https://github.com/guanguans/notify/compare/v1.7.2...v1.8.0
[v1.7.2]: https://github.com/guanguans/notify/compare/v1.7.1...v1.7.2
[v1.7.1]: https://github.com/guanguans/notify/compare/v1.7.0...v1.7.1
[v1.7.0]: https://github.com/guanguans/notify/compare/v1.6.0...v1.7.0
[v1.6.0]: https://github.com/guanguans/notify/compare/v1.5.0...v1.6.0
[v1.5.0]: https://github.com/guanguans/notify/compare/v1.4.4...v1.5.0
[v1.4.4]: https://github.com/guanguans/notify/compare/v1.4.3...v1.4.4
[v1.4.3]: https://github.com/guanguans/notify/compare/v1.4.2...v1.4.3
[v1.4.2]: https://github.com/guanguans/notify/compare/v1.4.1...v1.4.2
[v1.4.1]: https://github.com/guanguans/notify/compare/v1.4.0...v1.4.1
[v1.4.0]: https://github.com/guanguans/notify/compare/v1.3.1...v1.4.0
[v1.3.1]: https://github.com/guanguans/notify/compare/v1.3.0...v1.3.1
[v1.3.0]: https://github.com/guanguans/notify/compare/v1.2.0...v1.3.0
[v1.2.0]: https://github.com/guanguans/notify/compare/v1.1.2...v1.2.0
[v1.1.2]: https://github.com/guanguans/notify/compare/v1.1.1...v1.1.2
[v1.1.1]: https://github.com/guanguans/notify/compare/v1.1.0...v1.1.1
[v1.1.0]: https://github.com/guanguans/notify/compare/v1.0.5...v1.1.0
[v1.0.5]: https://github.com/guanguans/notify/compare/v1.0.4...v1.0.5
[v1.0.4]: https://github.com/guanguans/notify/compare/v1.0.3...v1.0.4
[v1.0.3]: https://github.com/guanguans/notify/compare/v1.0.2...v1.0.3
[v1.0.2]: https://github.com/guanguans/notify/compare/v1.0.1...v1.0.2
[v1.0.1]: https://github.com/guanguans/notify/compare/v1.0.0...v1.0.1
