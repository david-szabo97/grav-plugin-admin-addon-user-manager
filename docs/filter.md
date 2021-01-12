# Filter expression

## Users

Since version 2.5.0 it's now possible to do simple searches rather than advanced filter expressions. Just type whatever you are looking for and it will search in the `username`, `email`, and `fullName` fields of the user.

### Available variables

- `user.username` username of the user
- `user.email` email of the user
- `user.fullName` full name of the user
- `user.title` title of the user
- `user.language` language of the user
- `user.groups` array of the groups the user is in
- `user.access` an array which contains the permissions of the user

### Available methods

- `user.authorize('example.permission')` checks whether user has access to the given permission or not (take groups into account)

### Examples

- filter users by permissions
  ```
  user.authorize('admin.super')
  ```
- show users who are in the 'paid' group
  ```
  'paid' in user.groups
  ```
- show users without groups
  ```
  count(user.groups) > 0
  ```
- show users with access to 'admin.users'
  ```
  group.authorize('admin.users') and groups.users > 0
  ```
- show users with gmail email provider
  ```
  user.email matches '/@gmail.com/'
  ```

## Groups

Since version 2.5.0 it's now possible to do simple searches rather than advanced filter expressions. Just type whatever you are looking for and it will search in the `groupname`, `readableName`, and `description` fields of the group.

### Available variables

- `group.groupname` name of the group
- `group.readableName` readable name of the group
- `group.description` description of the group
- `group.icon` icon of the group
- `group.access` an array which contains the permissions of the group

### Available methods

- `group.authorize('example.permission')` checks whether group has access to the given permission or not

### Examples

- filter groups by permissions
  ```
  group.authorize('admin.super')
  ```
- show groups with more than 5 users
  ```
  group.users > 5
  ```
- show empty groups
  ```
  group.users == 0
  ```
- show groups with access to 'admin.users' and not empty
  ```
  group.authorize('admin.users') and groups.users > 0
  ```
- show groups which contains 'admin' in its description
  ```
  group.description matches '/admin/'
  ```
