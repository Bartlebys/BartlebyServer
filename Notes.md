# Notes on Performance and Scalability

You can deploy using a Docker container or not.
The deployment can be monolithic or you can dissociate the Main MongoDB instance, the  EndPoints, and SSETrigger micro service.

## You can setup multiple node for the `SSETrigger`.

Those node can connect to a MongoDB replicas in read Only.

## The Trigger creation brake

The controller `relayTrigger` method uses `RunAndLock::`  that relies on a semaphore to block the triggers creations before inserting a new one.
This is required to compute the trigger index and guarantee a consistent trigger Order sequence within an observationUID.
We would need an incremental counter per observationUID be able to remove that brake.