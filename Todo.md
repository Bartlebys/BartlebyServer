# Todo V1

# Todo V1.x

## Client Side

## Server Side 
- [ ] Support Multiple filters IN & Out 
- [ ] Core search path resolution cache
- [ ] PHP serialization / Deserialization.
- [ ] Module support ( with configuration best practices )
- [ ] Support module based namespaces (actually we use Bartleby\EndPoints)
- [ ] Test Bartleby version from Header to detect version conflicts ( Client Version should be <= bartleby's Core version)
- [X] Generative BUG : DO NOT use allOF there is a Bug on Composition of Composition in SwaggerToFlexions -> check AbstractContext.
- [X] **Document Extraction** FullDataspace Extraction by Dataspace Owner (including triggers) to provide movable Document (reallocation) 
- [ ] **Redis triggers SSE**
- [ ] **Bartleby relay mode**
   - [ ] a redis relay via SSE without ACL (the connection is controlled but all the data flow is performed without any Access control).
 - [X] Generation of dependencies ( 1-N , 1-1, N-N ) creation & deletion cascading automation.

# Todo V2.0

## Client Side 


## Server Side 
- [ ] Support Standardized Pagination for Getter endpoints (V1.0)
- [ ] Incremental local storage (SQLite) ? 
- [ ] Swift Models with optionals or not (extracted from swagger's requirements, including designated initializers)
- [ ] Patch (for large object graph)

---

# V1 Implemented features 
- [X] RestFull End Points
- [X] Web pages foundations
- [X] SSE support 
- [X] Multi level ACL
   - [X] Declarative static super admins.
- [X] Configurable Routes and Alias Support
- [X] Sessions via Cookies
- [X] Sessions via KVID
- [X] Overloads (Allows to overload generated EndPoints)
- [X] **Triggers** == Rich Data Push Support (via SSE querying MongoDB) 
- [X] Static assets
- [-] Tools
   - [X] Generative Destructive installer
   - [X] Ephemeral entities support
   - [X] Post Installers
   - [X] Bartleby Flexions 
   - [ ] Diagnostic Tools
   - [X] Simplified Installer
- [X] Bartleby **Flexions** client and server side code generator.
- [X] Rich Swift 3.X client lib
   - [X] OSX
   - [-] iOS 
   - [ ] Linux