const Midgard = imports.gi.Midgard;
Midgard.init ();

config = new Midgard.Config();
config.read_file_at_path("/tmp/Midgard2CR/midgard2.conf");

/* Establish connection */
mgd = new Midgard.Connection();
mgd.open_config(config);

/* Get object by specified path */
ntFolder = Midgard.SchemaObjectFactory.get_object_by_path(mgd, 'ntFolder', '/jackalope/tests_general_base');

/* Read jcr:primaryType */
let type = "";
ntFolder.get_property('jcr-primaryType', type);
print (type);

ref = new Midgard.ReflectorProperty({dbclass:"nt_folder"});
print(ref.get_user_value('jcr-created', 'isAutoCreated'));

print (ntFolder.valueOf());

Midgard.close();

