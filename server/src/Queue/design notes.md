# On API tokens...

Queues have owners, files have owners, they don't have to be the same

- file.user_id --> file.queue_id (add queues table)
- file.tags --> parse to _new_ file.user_id

Separate queue owner vs. file owner by scopes

Password-less login for file owner generates access token
